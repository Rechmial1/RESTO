<?php

namespace Modules\CashRegister\Services;

use Modules\CashRegister\Entities\CashRegisterSetting;
use App\Models\User;
use Modules\CashRegister\Entities\CashRegisterSession;

class RegisterForceOpenService
{
    /**
     * Check if user should be forced to open register after login
     */
    public static function shouldForceOpenRegister(User $user): bool
    {
        // Require permission to open register
        if (!user_can('Open Cash Register')) {
            return false;
        }

        $settings = CashRegisterSetting::where('restaurant_id', $user->restaurant_id)->first();
        
        if (!$settings || !$settings->force_open_after_login) {
            return false;
        }

        // Check if user has any of the selected roles
        $userRoleIds = $user->roles->pluck('id')->toArray();
        $forceOpenRoleIds = $settings->force_open_roles ?? [];

        // Coerce to array in case stored as JSON string or CSV
        if (is_string($forceOpenRoleIds)) {
            $decoded = json_decode($forceOpenRoleIds, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $forceOpenRoleIds = $decoded;
            } else {
                $forceOpenRoleIds = array_filter(array_map('trim', explode(',', $forceOpenRoleIds)));
            }
        }

        if (is_null($forceOpenRoleIds)) {
            $forceOpenRoleIds = [];
        }

        if (!is_array($forceOpenRoleIds)) {
            $forceOpenRoleIds = (array) $forceOpenRoleIds;
        }

        // Normalize to integers/strings consistently
        $forceOpenRoleIds = array_map(static function ($value) {
            return is_numeric($value) ? (int) $value : $value;
        }, $forceOpenRoleIds);

        return !empty(array_intersect($userRoleIds, $forceOpenRoleIds));
    }

    /**
     * Check if user already has an open register session for current branch
     */
    public static function hasOpenRegisterSession(User $user): bool
    {
        return CashRegisterSession::where('opened_by', $user->id)
            ->where('status', 'open')
            ->where('branch_id', branch()->id ?? 0)
            ->exists();
    }

    /**
     * Get the redirect URL for force opening register
     */
    public static function getForceOpenRedirectUrl(): string
    {
        return route('cashregister.cashier');
    }
}
