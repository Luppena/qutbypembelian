<?php

namespace App\Filament\Traits;

use App\Enums\UserRole;

/**
 * Trait untuk mengontrol akses Resource/Page berdasarkan role user.
 *
 * Cara pakai:
 *   1. Tambahkan `use HasRoleAccess;` di class Resource/Page
 *   2. Definisikan property: `protected static array $allowedRoles = ['finance', 'operasional'];`
 *
 * Jika $allowedRoles kosong, tidak ada role yang bisa akses (hidden from all).
 */
trait HasRoleAccess
{
    public static function canAccess(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        $allowedRoles = static::$allowedRoles ?? [];

        if (empty($allowedRoles)) {
            return false;
        }

        $userRole = $user->role instanceof UserRole
            ? $user->role->value
            : (string) $user->role;

        if ($userRole === 'admin' || $userRole === UserRole::Admin->value) {
            return true;
        }

        return in_array($userRole, $allowedRoles, true);
    }
}
