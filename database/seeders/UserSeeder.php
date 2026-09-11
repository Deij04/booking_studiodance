<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'superadmin@gmail.test'],
            [
                'name' => 'Super Admin',
                'phone' => '081200000001',
                'password' => Hash::make('password'),
                'role' => 'super_admin',
                'status' => 'active',
            ]
        );

        User::firstOrCreate(
            ['email' => 'admin@gmail.test'],
            [
                'name' => 'Admin',
                'phone' => '081200000002',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'status' => 'active',
            ]
        );

        User::firstOrCreate(
            ['email' => 'customer1@gmail.test'],
            [
                'name' => 'Customer 1',
                'phone' => '081200000003',
                'password' => Hash::make('password'),
                'role' => 'customer',
                'status' => 'active',
            ]
        );

        User::factory()->customer()->count(5)->create();
    }
}
