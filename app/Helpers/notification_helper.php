<?php

use App\Models\User;
use App\Models\Students;
use App\Models\Notification;
use App\Models\NotificationUser;
use App\Services\CachingService;
use Google\Client;

function send_notification($userIds, $title, $body, $type, $customData = [])
{
    $schoolId = $customData['school_id'] ?? (auth()->check() ? auth()->user()->school_id : null);
    
    if (!$schoolId) {
        $userIdsArray = (array)$userIds;
        if (!empty($userIdsArray)) {
            $firstUserId = reset($userIdsArray);
            $userForSchool = User::find($firstUserId);
            if ($userForSchool) {
                $schoolId = $userForSchool->school_id;
            }
        }
    }

    if (!$schoolId) {
        throw new Exception('School ID missing');
    }

    $cache = app(CachingService::class);
    $sessionYear = $cache->getDefaultSessionYear($schoolId);

    /* =====================================
     | 1️⃣ CREATE SINGLE MASTER NOTIFICATION
     =====================================*/
    $notification = Notification::create([
        'school_id'       => $schoolId,
        'title'           => $title,
        'message'         => $body,
        'send_to'         => 'multiple',
        'event_date'      => $customData['event_date'] ?? null, 
        'image'           => $customData['image'] ?? null,     
        'session_year_id' => $sessionYear->id,
        'sender_id'       => $customData['sender_id'] ?? (auth()->check() ? auth()->user()->id : null),
    ]);

    $classSectionIds = $customData['class_section_ids'] ?? [];
    if (empty($classSectionIds) && request()->has('class_section_id')) {
        $classSectionIds = is_array(request()->class_section_id) ? request()->class_section_id : [request()->class_section_id];
    }

    if (!empty($classSectionIds)) {
        foreach ($classSectionIds as $class_id) {
            \App\Models\NotificationClass::create([
                'notification_id' => $notification->id,
                'class_section_id' => $class_id
            ]);
        }
    }

    /* =====================================
     | 2️⃣ LOOP ALL USERS
     =====================================*/
    foreach ((array)$userIds as $uid) {

        $user = User::find((int)$uid);
        if (!$user) {
            continue;
        }

        /* =====================================
         | STUDENT → PARENT
         =====================================*/
        if ($user->hasRole('Student')) {

            $student = Students::where('user_id', $user->id)->first();
            if (!$student || !$student->guardian_id) {
                continue;
            }

            $receiver = User::find($student->guardian_id);
            if (!$receiver) {
                continue;
            }

            $studentName = trim(
                ($user->first_name ?? '') . ' ' . ($user->last_name ?? '')
            );

            $finalTitle = $title . ' - ' . $studentName;
            $studentId  = $student->id;

        } else {
            /* =====================================
             | NON-STUDENT → SELF
             =====================================*/
            $receiver   = $user;
            $finalTitle = $title;
            $studentId  = null;
        }
        $userRole = $receiver->getRoleNames()->first(); 

        /* =====================================
         | notification_users (ALWAYS)
         =====================================*/
        NotificationUser::create([
            'notification_id' => $notification->id,
            'user_id'         => $receiver->id,
            'user_role'       => $userRole,
            'student_id'      => $studentId,
            'sent_at'         => now(),
        ]);

        /* =====================================
         | PUSH NOTIFICATION
         =====================================*/
        if (!$receiver->fcm_id) {
            continue;
        }

        $projectId   = $cache->getSystemSettings('firebase_project_id');
        $accessToken = getAccessToken();

        $payload = [
            "message" => [
                "token" => $receiver->fcm_id,
                "notification" => [
                    "title" => $finalTitle,
                    "body"  => $body
                ],
                "data" => [
                    "title"           => $finalTitle,
                    "body"            => $body,
                    "type"            => $type,
                    "notification_id" => (string)$notification->id,
                    "student_id"      => $studentId ? (string)$studentId : "",
                    "send_to"         => $receiver->getRoleNames()->first() ?? '',
                    "custom_data"     => json_encode($customData),
                ],
                "android" => [
                    "priority" => "high",
                    "notification" => [
                        "sound" => "default",
                        "click_action" => "FLUTTER_NOTIFICATION_CLICK"
                    ]
                ],
                "apns" => [
                    "headers" => [
                        "apns-priority" => "10"
                    ],
                    "payload" => [
                        "aps" => [
                            "sound" => "default",
                            "mutable-content" => 1,
                            "content-available" => 1
                        ]
                    ]
                ]
            ]
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
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_POSTFIELDS => json_encode($payload),
        ]);

        curl_exec($ch);
        curl_close($ch);
    }

    return true;
}

/* =====================================
 | ACCESS TOKEN
 =====================================*/
function getAccessToken()
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
