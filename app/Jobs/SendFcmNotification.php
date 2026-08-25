<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\FcmToken;
use App\Models\School;
use App\Services\CachingService;
use App\Services\FcmTokenService;
use Google\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

final class SendFcmNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public int $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public int $timeout = 60;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public int $backoff = 30;

    /**
     * Create a new job instance.
     *
     * @param int $schoolId The school ID from the main database
     * @param int $fcmTokenId The FCM token ID in the school database
     * @param string $title
     * @param string $body
     * @param string $type
     * @param array<string, mixed> $customData
     */
    public function __construct(
        private readonly int $schoolId,
        private readonly int $fcmTokenId,
        private readonly string $title,
        private readonly string $body,
        private readonly string $type,
        private readonly array $customData = []
    ) {}

    /**
     * Execute the job.
     *
     * @param CachingService $cache
     * @param FcmTokenService $fcmTokenService
     * @return void
     */
    public function handle(CachingService $cache, FcmTokenService $fcmTokenService): void
    {
        // Ensure we start with main database connection
        DB::setDefaultConnection('mysql');

        try {
            // Step 1: Get school information from main database
            $school = School::on('mysql')->find($this->schoolId);

            if (!$school) {
                Log::error("School not found for ID: {$this->schoolId}");
                return;
            }

            // Step 2: Switch to school-specific database
            DB::setDefaultConnection('school');
            Config::set('database.connections.school.database', $school->database_name);
            DB::purge('school');
            DB::connection('school')->reconnect();
            DB::setDefaultConnection('school');

            // Step 3: Fetch FCM token from school database
            $fcmToken = FcmToken::find($this->fcmTokenId);

            if (!$fcmToken) {
                Log::info('FCM token not found or deleted', [
                    'token_id' => $this->fcmTokenId,
                    'school_id' => $this->schoolId,
                ]);
                return;
            }

            $projectId = $cache->getSystemSettings('firebase_project_id');

            if (!$projectId) {
                Log::warning('Firebase project ID not configured');
                return;
            }

            $url = 'https://fcm.googleapis.com/v1/projects/' . $projectId . '/messages:send';
            $accessToken = $this->getAccessToken($cache);

            if (!$accessToken) {
                Log::error('Failed to get Firebase access token');
                return;
            }

            // Convert custom data values to strings (FCM requires string values in data payload)
            $customDataStrings = array_map(function ($value) {
                if (is_array($value)) {
                    return json_encode($value);
                }
                return (string) $value;
            }, $this->customData);

            // Build payload based on device type
            $data = $this->buildPayload($fcmToken, $customDataStrings);

            // Send notification
            $response = $this->sendFcmNotification($url, $accessToken, $data);

            // Handle response
            $this->handleResponse($response, $fcmToken, $fcmTokenService);
        } catch (Throwable $e) {
            Log::error('SendFcmNotification job failed', [
                'school_id' => $this->schoolId,
                'token_id' => $this->fcmTokenId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        } finally {
            // Switch back to main database connection
            $this->switchBackToMainDatabase();
        }
    }

    /**
     * Build FCM payload based on device type.
     *
     * @param FcmToken $fcmToken
     * @param array<string, string> $customDataStrings
     * @return array<string, mixed>
     */
    private function buildPayload(FcmToken $fcmToken, array $customDataStrings): array
    {
        $basePayload = [
            'message' => [
                'token' => $fcmToken->fcm_token,
                'data' => array_merge([
                    'title' => $this->title,
                    'body' => $this->body,
                    'type' => $this->type,
                ], $customDataStrings),
            ],
        ];

        if ($fcmToken->device_type === 'web') {
            return $this->buildWebPayload($basePayload, $customDataStrings);
        }

        return $this->buildMobilePayload($basePayload, $customDataStrings);
    }

    /**
     * Build payload for mobile devices (Android & iOS).
     *
     * @param array<string, mixed> $basePayload
     * @param array<string, string> $customDataStrings
     * @return array<string, mixed>
     */
    private function buildMobilePayload(array $basePayload, array $customDataStrings): array
    {
        $basePayload['message']['notification'] = [
            'title' => $this->title,
            'body' => $this->body,
        ];

        if (isset($this->customData['image'])) {
            $basePayload['message']['notification']['image'] = $this->customData['image'];
        }

        $basePayload['message']['android'] = [
            'notification' => [
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                'sound' => 'default',
            ],
            'priority' => 'high',
        ];

        $basePayload['message']['apns'] = [
            'headers' => [
                'apns-priority' => '10',
            ],
            'payload' => array_merge([
                'aps' => [
                    'alert' => [
                        'title' => $this->title,
                        'body' => $this->body,
                    ],
                    'sound' => 'default',
                    'mutable-content' => 1,
                    'content-available' => 1,
                ],
                'type' => $this->type,
            ], $customDataStrings),
        ];

        return $basePayload;
    }

    /**
     * Build payload for web devices.
     *
     * @param array<string, mixed> $basePayload
     * @param array<string, string> $customDataStrings
     * @return array<string, mixed>
     */
    private function buildWebPayload(array $basePayload, array $customDataStrings): array
    {
        $iconUrl = $this->getWebNotificationIcon();

        $webNotification = [
            'title' => $this->title,
            'body' => $this->body,
            'icon' => $iconUrl,
            'badge' => $iconUrl,
            'requireInteraction' => false,
            'silent' => false,
            'sound' => 'default',
        ];

        if (isset($this->customData['image']) && !empty($this->customData['image'])) {
            $imageUrl = $this->customData['image'];
            if (!filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                $imageUrl = url($imageUrl);
            }
            $webNotification['image'] = $imageUrl;
        }

        $basePayload['message']['webpush'] = [
            'notification' => $webNotification,
        ];

        return $basePayload;
    }

    /**
     * Get web notification icon URL.
     *
     * @return string
     */
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

    /**
     * Send FCM notification via cURL.
     *
     * @param string $url
     * @param string $accessToken
     * @param array<string, mixed> $data
     * @return array<string, mixed>|null
     */
    private function sendFcmNotification(string $url, string $accessToken, array $data): ?array
    {
        $encodedData = json_encode($data);

        $headers = [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json',
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $encodedData);

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($result === false) {
            Log::error('FCM notification cURL error', [
                'error' => $error,
                'token_id' => $this->fcmTokenId,
            ]);
            return null;
        }

        $response = json_decode($result, true);

        if ($httpCode !== 200 || (isset($response['error']) && !empty($response['error']))) {
            Log::warning('FCM notification error', [
                'http_code' => $httpCode,
                'response' => $response,
                'token_id' => $this->fcmTokenId,
            ]);
        }

        return $response;
    }

    /**
     * Handle FCM response and remove invalid tokens.
     *
     * @param array<string, mixed>|null $response
     * @param FcmToken $fcmToken
     * @param FcmTokenService $fcmTokenService
     * @return void
     */
    private function handleResponse(?array $response, FcmToken $fcmToken, FcmTokenService $fcmTokenService): void
    {
        if (!$response) {
            return;
        }

        // Check for invalid token errors
        if (isset($response['error'])) {
            $errorCode = $response['error']['code'] ?? null;
            $errorMessage = $response['error']['message'] ?? '';

            // FCM error codes that indicate invalid token
            $invalidTokenCodes = [
                'NOT_FOUND',
                'INVALID_ARGUMENT',
                'UNREGISTERED',
                'INVALID_REGISTRATION',
            ];

            if (in_array($errorCode, $invalidTokenCodes, true) || 
                stripos($errorMessage, 'invalid') !== false ||
                stripos($errorMessage, 'not found') !== false ||
                stripos($errorMessage, 'unregistered') !== false) {
                // Remove invalid token
                $fcmTokenService->removeInvalidToken($fcmToken->fcm_token);
                Log::info('Removed invalid FCM token', [
                    'token_id' => $fcmToken->id,
                    'error_code' => $errorCode,
                ]);
            }
        } else {
            // Success - update last_used_at
            $fcmToken->touchLastUsed();
        }
    }

    /**
     * Get Firebase access token.
     *
     * @param CachingService $cache
     * @return string|null
     */
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

    /**
     * Switch back to main database connection.
     *
     * @return void
     */
    private function switchBackToMainDatabase(): void
    {
        try {
            DB::setDefaultConnection('mysql');
        } catch (Throwable $e) {
            Log::error('Failed to switch back to main database: ' . $e->getMessage());
        }
    }

    /**
     * Handle a job failure.
     *
     * @param Throwable $exception
     * @return void
     */
    public function failed(Throwable $exception): void
    {
        Log::error('SendFcmNotification job failed', [
            'school_id' => $this->schoolId,
            'token_id' => $this->fcmTokenId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        // Ensure we're back on main database
        $this->switchBackToMainDatabase();
    }
}

