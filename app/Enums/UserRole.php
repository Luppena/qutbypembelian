<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case SuperAdmin = 'super_admin';
    case Finance = 'finance';
    case Operasional = 'operasional';
    case AdminGudang = 'admin_gudang';
    case Gudang = 'gudang';

    public function label(): string
    {
        return match ($this) {
            self::Admin       => 'Admin',
            self::SuperAdmin  => 'Super Admin',
            self::Finance     => 'Finance',
            self::Operasional => 'Operasional',
            self::AdminGudang => 'Admin Gudang',
            self::Gudang      => 'Gudang',
        };
    }
}
