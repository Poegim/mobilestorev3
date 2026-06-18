<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['login' => 'admin'],
            [
                'name' => 'Administrator',
                'email' => 'admin@mobilestore.pl',
                'password' => Hash::make('admin123'),
                'privilege' => 5,
                'contact_id' => 0,
            ]
        );

        $this->command->info('Admin user created — login: admin / password: admin123');
    }
}