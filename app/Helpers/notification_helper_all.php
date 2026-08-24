<?php

use App\Models\User;
use App\Models\Students;
use App\Models\Notification;
use App\Models\NotificationUser;
use App\Services\CachingService;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Google\Client;

function send_notification_all($notificationModel, $userIds, $type, $customData = [])
{
    // ✅ Wrap context in array
    Log::info('Custom Data Received', ['customData' => $customData]);

    /* ===============================
     | SAFETY: SCHOOL ID
     ===============================*/
    $schoolId = $customData['school_id'] ?? null;

    if (!$schoolId) {
        Log::error('School ID missing in send_notification_all', [
            'customData' => $customData
        ]);
        return false;
    }

    /* ===============================
     | SESSION YEAR (SAFE)
     ===============================*/
    $cache = app(CachingService::class);
    $sessionYear = $cache->getDefaultSessionYear($schoolId);

    /* ===============================
     | 1️⃣ CREATE MASTER NOTIFICATION
     ===============================*/
    $notification = Notification::create([
        'school_id'       => $schoolId,
        'title'           => $notificationModel->title,
        'message'         => $notificationModel->message,
        'send_to'         => 'multiple',
        'event_date'      => $customData['event_date'] ?? null,
        'image'           => $customData['image'] ?? null,
        'session_year_id' => $sessionYear?->id,
    ]);

    /* ===============================
     | 2️⃣ DUPLICATE PREVENT
     ===============================*/
    $processedReceivers = [];

    foreach ((array)$userIds as $uid) {

        $user = User::find((int)$uid);
        if (!$user) continue;

        $receiver   = null;
        $studentId  = null;
        $userRole   = null;
        $finalTitle = $notification->title;

        if ($user->hasRole('Student')) {

            $student = Students::where('user_id', $user->id)->first();
            if (!$student || !$student->guardian_id) continue;

            if (in_array($student->guardian_id, $processedReceivers)) continue;

            $receiver = User::find($student->guardian_id);
            if (!$receiver) continue;

            $processedReceivers[] = $receiver->id;

            $finalTitle .= ' - ' . trim($user->first_name . ' ' . $user->last_name);
            $studentId  = $student->id;
            $userRole   = 'Guardian';

        } else {

            if (in_array($user->id, $processedReceivers)) continue;

            $receiver = $user;
            $processedReceivers[] = $receiver->id;
            $userRole = $user->getRoleNames()->first();
        }

        /* ===============================
         | 3️⃣ SAVE notification_users
         ===============================*/
        NotificationUser::create([
            'notification_id' => $notification->id,
            'user_id'         => $receiver->id,
            'user_role'       => $userRole,
            'student_id'      => $studentId,
            'sent_at'         => now(),
        ]);

        /* ===============================
         | 4️⃣ PUSH (FCM)
         ===============================*/
        if (!$receiver->fcm_id) continue;

        $projectId   = $cache->getSystemSettings('firebase_project_id');
        $accessToken = getAccessToken_all();

        $payload = [
            "message" => [
                "token" => $receiver->fcm_id,
                "notification" => [
                    "title" => $finalTitle,
                    "body"  => $notification->message,
                ],
                "data" => [
                    "type"            => $type,
                    "notification_id" => (string)$notification->id,
                    "student_id"      => (string)$studentId,
                    "send_to"         => $userRole,
                ],
            ],
        ];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send",
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $accessToken,
                'Content-Type: application/json',
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
        ]);

        curl_exec($ch);
        curl_close($ch);
    }

    return true;
}

/* =====================================
 | 5️⃣ FIREBASE ACCESS TOKEN
 =====================================*/
function getAccessToken_all()
{
    $cache = app(CachingService::class);

    $file = $cache->getSystemSettings('firebase_service_file');
    $file = explode("storage/", $file ?? '');
    $file = end($file);

    $path = base_path('public/storage/' . $file);

    $client = new Client();
    $client->setAuthConfig($path);
    $client->setScopes(['https://www.googleapis.com/auth/firebase.messaging']);

    return $client->fetchAccessTokenWithAssertion()['access_token'];
}