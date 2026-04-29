<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User Finance
        User::updateOrCreate(
            ['email' => 'finance@qutby.com'],
            [
                'name' => 'Finance Qutby',
                'password' => bcrypt('password'),
                'role' => 'finance',
            ]
        );

        // User Operasional
        User::updateOrCreate(
            ['email' => 'ops@qutby.com'],
            [
                'name' => 'Operasional Qutby',
                'password' => bcrypt('password'),
                'role' => 'operasional',
            ]
        );
    }
}
