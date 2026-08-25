<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\FcmToken;
use App\Models\School;
use App\Models\User;
use App\Services\CachingService;
use App\Services\FcmTokenService;
use Google\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\Pool;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

final class BulkNotificationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 3;
    public int $timeout = 120;
    public int $backoff = 30;

    /**
     * @param int   $schoolId
     * @param array $userIds
     * @param string $title
     * @param string $body
     * @param string $type
     * @param array{
     *   student_map?: array<int,int>,
     *   guardian_map?: array<int,array<int>|int>,
     *   image?: string
     * } $customData
     */
    public function __construct(
        private readonly int $schoolId,
        private readonly array $userIds,
        private readonly string $title,
        private readonly string $body,
        private readonly string $type,
        private readonly array $customData = []
    ) {
    }

    public function handle(
        CachingService $cache,
        FcmTokenService $tokenService
    ): void {
        DB::setDefaultConnection('mysql');

        try {
            $school = School::on('mysql')->find($this->schoolId);
            if (!$school)
                return;

            Config::set('database.connections.school.database', $school->database_name);
            DB::purge('school');
            DB::connection('school')->reconnect();
            DB::setDefaultConnection('school');

            $internal = $this->customData['internal'] ?? [];
            $payloadBase = array_map(
                'strval',
                $this->customData['payload'] ?? []
            );

            $tokens = $tokenService->getUsersTokens($this->userIds);
            if ($tokens->isEmpty())
                return;

            $targets = $this->resolveTargets($tokens, $internal);
            if (empty($targets))
                return;

            $accessToken = $this->getAccessToken($cache);
            if (!$accessToken)
                return;

            $url = "https://fcm.googleapis.com/v1/projects/{$cache->getSystemSettings('firebase_project_id')}/messages:send";
            $sectionMap = $internal['section_map'] ?? [];
            Http::pool(function ($pool) use ($targets, $payloadBase, $accessToken, $url, $tokenService, $sectionMap) {

                foreach ($targets as $target) {

                    $token = $target['token'];
                    $childId = $target['child_id'];
                    $classSubjectId = null;

                    if ($childId) {
                        // child_id == student_id
                        $student = \App\Models\Students::find($childId, ['class_section_id']);

                        if ($student && isset($sectionMap[$student->class_section_id])) {
                            $classSubjectId = $sectionMap[$student->class_section_id];
                        }
                    }

                    $data = array_merge([
                        'title' => $this->title,
                        'body' => $this->body,
                        'type' => $this->type,
                    ], $payloadBase);

                    if ($childId) {
                        $data['child_id'] = (string) $childId;
                    }

                    if ($classSubjectId) {
                        $data['class_subject_id'] = (string) $classSubjectId;
                    }

                    Log::info('Sending FCM notification', [
                        'data' => $data
                    ]);

                    $payload = [
                        'message' => [
                            'token' => $token->fcm_token,
                            'data' => $data,
                            'notification' => [
                                'title' => $this->title,
                                'body' => $this->body,
                            ],
                            'android' => [
                                'priority' => 'high',
                                'notification' => [
                                    'sound' => 'default',
                                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                                    'tag' => 'announcement_' . ($childId ?? uniqid()),
                                ],
                            ],
                        ],
                    ];

                    $pool
                        ->withToken($accessToken)
                        ->post($url, $payload)
                        ->then(fn($r) => $this->handleResponse($r->json(), $token, $tokenService));
                }
            });

        } finally {
            DB::setDefaultConnection('mysql');
        }
    }

    /* ================= CORE FIX ================= */

    private function resolveTargets($tokens, array $internal): array
    {
        $targets = [];

        $studentMap = $internal['student_map'] ?? [];
        $guardianMap = $internal['guardian_map'] ?? [];

        foreach ($tokens as $token) {
            $userId = $token->user_id;

            if (isset($guardianMap[$userId])) {
                foreach ($guardianMap[$userId] as $childId) {
                    $targets[] = [
                        'token' => $token,
                        'child_id' => $childId,
                    ];
                }
                continue;
            }

            if (isset($studentMap[$userId])) {
                $targets[] = [
                    'token' => $token,
                    'child_id' => $studentMap[$userId],
                ];
                continue;
            }

            $targets[] = [
                'token' => $token,
                'child_id' => null,
            ];
        }

        return $targets;
    }

    /* ================= Helpers ================= */

    private function handleResponse(?array $response, FcmToken $token, FcmTokenService $service): void
    {
        if (!$response)
            return;

        if (isset($response['error'])) {

            $status = $response['error']['status'] ?? null;

            if (in_array($status, ['NOT_FOUND', 'UNREGISTERED'], true)) {
                $service->removeInvalidToken($token->fcm_token);
                return;
            }

            // Payload / request errors → log only
            Log::warning('FCM send failed', [
                'status' => $status,
                'message' => $response['error']['message'] ?? null,
                'token_id' => $token->id,
            ]);

            return;
        }

        $token->touchLastUsed();
    }

    private function getAccessToken(CachingService $cache): ?string
    {
        try {
            $fileName = $cache->getSystemSettings('firebase_service_file');
            $data = explode('storage/', $fileName ?? '');
            $fileName = end($data);

            $filePath = base_path('public/storage/' . $fileName);

            if (!file_exists($filePath)) {
                Log::error('Firebase service file not found', ['path' => $filePath]);
                return null;
            }

            $client = new Client();
            $client->setAuthConfig($filePath);
            $client->setScopes(['https://www.googleapis.com/auth/firebase.messaging']);
            $accessToken = $client->fetchAccessTokenWithAssertion()['access_token'] ?? null;

            return $accessToken;
        } catch (Throwable $e) {
            Log::error('Failed to get Firebase access token', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return null;
        }
    }

    private function getWebNotificationIcon(): string
    {
        $defaultIcon = asset('assets/images/favicon.png');

        if (isset($this->customData['image']) && !empty($this->customData['image'])) {
            $image = $this->customData['image'];
            if (filter_var($image, FILTER_VALIDATE_URL)) {
                return $image;
            }
            return url($image);
        }

        return $defaultIcon;
    }
}
