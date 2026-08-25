<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\FcmToken;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class FcmTokenService
{
    /**
     * Store or update FCM token for a user and device.
     *
     * @param User $user
     * @param string $fcmToken
     * @param string $deviceType
     * @param string|null $deviceId
     * @return FcmToken
     */
    public function storeOrUpdateToken(
        User $user,
        string $fcmToken,
        string $deviceType = 'android',
        ?string $deviceId = null
    ): FcmToken {
        // Validate device type
        $deviceType = $this->normalizeDeviceType($deviceType);

        // Check if token already exists
        $existingToken = FcmToken::where('fcm_token', $fcmToken)->first();

        if ($existingToken) {
            // If token belongs to different user, update it
            if ($existingToken->user_id !== $user->id) {
                $existingToken->user_id = $user->id;
                $existingToken->device_type = $deviceType;
                $existingToken->device_id = $deviceId;
                $existingToken->last_used_at = now();
                $existingToken->save();
                return $existingToken;
            }

            // If token belongs to same user, just update last_used_at
            $existingToken->touchLastUsed();
            if ($existingToken->device_type !== $deviceType || $existingToken->device_id !== $deviceId) {
                $existingToken->device_type = $deviceType;
                $existingToken->device_id = $deviceId;
                $existingToken->save();
            }
            return $existingToken;
        }

        // Check if user already has a token for this device_id (if provided)
        if ($deviceId) {
            $deviceToken = FcmToken::forUser($user->id)
                ->where('device_id', $deviceId)
                ->where('device_type', $deviceType)
                ->first();

            if ($deviceToken) {
                // Update existing device token
                $deviceToken->fcm_token = $fcmToken;
                $deviceToken->last_used_at = now();
                $deviceToken->save();
                return $deviceToken;
            }
        }

        // Create new token
        return FcmToken::create([
            'user_id' => $user->id,
            'fcm_token' => $fcmToken,
            'device_type' => $deviceType,
            'device_id' => $deviceId,
            'last_used_at' => now(),
        ]);
    }

    /**
     * Remove or deactivate FCM token for a specific device.
     *
     * @param User $user
     * @param string|null $fcmToken
     * @param string|null $deviceId
     * @return bool
     */
    public function removeToken(User $user, ?string $fcmToken = null, ?string $deviceId = null): bool
    {
        $query = FcmToken::forUser($user->id);

        if ($fcmToken) {
            $query->where('fcm_token', $fcmToken);
        }

        if ($deviceId) {
            $query->where('device_id', $deviceId);
        }

        $tokens = $query->get();

        if ($tokens->isEmpty()) {
            return false;
        }
        foreach ($tokens as $token) {
            $token->delete();
        }

        return true;
    }

    /**
     * Get all active FCM tokens for a user.
     *
     * @param User $user
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUserTokens(User $user)
    {
        return FcmToken::forUser($user->id)
            ->with('user')
            ->get();
    }

    /**
     * Get all active FCM tokens for multiple users.
     *
     * @param array<int> $userIds
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUsersTokens(array $userIds)
    {
        return FcmToken::forUsers($userIds)
            ->with('user')
            ->get();
    }

    /**
     * Get tokens grouped by device type for a user.
     *
     * @param User $user
     * @return array<string, \Illuminate\Database\Eloquent\Collection>
     */
    public function getUserTokensByDeviceType(User $user): array
    {
        $tokens = $this->getUserTokens($user);

        return [
            'android' => $tokens->where('device_type', 'android'),
            'ios' => $tokens->where('device_type', 'ios'),
            'web' => $tokens->where('device_type', 'web'),
        ];
    }

    /**
     * Clean up invalid or expired tokens.
     * This should be called when FCM returns an error for a token.
     *
     * @param string $fcmToken
     * @return bool
     */
    public function removeInvalidToken(string $fcmToken): bool
    {
        $token = FcmToken::where('fcm_token', $fcmToken)->first();

        if ($token) {
            Log::warning('Removing invalid FCM token', [
                'token_id' => $token->id,
                'user_id' => $token->user_id,
                'device_type' => $token->device_type,
            ]);

            return $token->delete();
        }

        return false;
    }

    /**
     * Normalize device type to valid enum value.
     *
     * @param string $deviceType
     * @return string
     */
    private function normalizeDeviceType(string $deviceType): string
    {
        $deviceType = strtolower(trim($deviceType));

        // Map common variations
        $mapping = [
            'mobile' => 'android',
            'iphone' => 'ios',
            'ipad' => 'ios',
            'desktop' => 'web',
            'browser' => 'web',
        ];

        if (isset($mapping[$deviceType])) {
            return $mapping[$deviceType];
        }

        // Validate against allowed values
        if (in_array($deviceType, ['android', 'ios', 'web'], true)) {
            return $deviceType;
        }

        // Default to android if invalid
        return 'android';
    }

    /**
     * Migrate existing tokens from users table (for backward compatibility).
     *
     * @param User $user
     * @return void
     */
    public function migrateUserTokens(User $user): void
    {
        // Migrate fcm_id (mobile token)
        if ($user->fcm_id && !empty(trim($user->fcm_id))) {
            $this->storeOrUpdateToken($user, trim($user->fcm_id), 'android');
        }

        // Migrate web_fcm (web token) if column exists
        if (isset($user->web_fcm) && $user->web_fcm && !empty(trim($user->web_fcm))) {
            $this->storeOrUpdateToken($user, trim($user->web_fcm), 'web');
        }
    }
}

