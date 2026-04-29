<?php

namespace App\Models;

use App\Enums\UserRole;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return in_array($this->role, [UserRole::Admin, UserRole::Finance, UserRole::Operasional]);
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isFinance(): bool
    {
        return $this->role === UserRole::Finance;
    }

    public function isOperasional(): bool
    {
        return $this->role === UserRole::Operasional;
    }

    public function hasRole(string|UserRole $role): bool
    {
        if (is_string($role)) {
            $role = UserRole::tryFrom($role);
        }

        return $this->role === $role;
    }
}
