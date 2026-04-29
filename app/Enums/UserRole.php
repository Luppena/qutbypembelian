<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Finance = 'finance';
    case Operasional = 'operasional';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::Finance => 'Finance',
            self::Operasional => 'Operasional',
        };
    }
}
