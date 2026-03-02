<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ProductionSeeder extends Seeder
{
    public function run(): void
    {
        // Create admin user if not exists
        User::firstOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name'     => 'Admin User',
                'password' => Hash::make('123456'),
                'role'     => 'admin',
                'status'   => 'active',
            ]
        );

        $this->command->info('Admin user ready: admin@gmail.com / 123456');
    }
}
