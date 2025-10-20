<?php

namespace Modules\Users\Services;

use App\Models\User;
use App\Models\UserSetting;

class AutoApplySettingsService
{
    /**
     * Create or update auto apply settings for the user.
     * If settings exist, they will be updated with provided values.
     */
    public function create(User $user, array $data): UserSetting
    {
        $setting = $user->settings()->first();

        $payload = [
            'auto_apply_enabled' => (bool)($data['auto_apply_enabled'] ?? false),
            'auto_apply_limit'   => (int)($data['auto_apply_limit'] ?? 0),
        ];

        if ($setting) {
            $setting->update($payload);
            return $setting->refresh();
        }

        return $user->settings()->create($payload);
    }
    public function incrementCount(User $user): UserSetting
    {
        $setting = $user->settings()->firstOrFail();
        $setting->increment('auto_apply_count');
        return $setting->refresh();
    }

    /**
     * Update only provided auto apply fields; create if missing.
     */
    public function update(User $user, array $data): UserSetting|array
    {
        $setting = $user->settings()->first();
        $balance = optional($user->credit)->balance ?? 0;

        if (!$setting) {
            $limit = isset($data['auto_apply_limit']) ? (int)$data['auto_apply_limit'] : 0;

            if ($limit > $balance) {
                return [
                    'error'   => true,
                    'message' => "Limit cannot be higher than current balance ({$balance}). 1"
                ];
            }

            return $user->settings()->create([
                'auto_apply_enabled' => isset($data['auto_apply_enabled']) ? (bool)$data['auto_apply_enabled'] : false,
                'auto_apply_limit'   => $limit,
            ]);
        }

        $payload = [];

        if (array_key_exists('auto_apply_enabled', $data)) {
            $payload['auto_apply_enabled'] = (bool)$data['auto_apply_enabled'];
        }

        if (array_key_exists('auto_apply_limit', $data)) {
            $addLimit = (int)$data['auto_apply_limit'];
            $newLimit = $setting->auto_apply_limit + $addLimit;

            if ($newLimit > $balance) {
                return [
                    'error'   => true,
                    'message' => "Limit cannot be higher than current balance ({$balance}). 2"
                ];
            }

            $payload['auto_apply_limit'] = $newLimit;
        }

        if (!empty($payload)) {
            $setting->update($payload);
        }

        return $setting->refresh();
    }
}
