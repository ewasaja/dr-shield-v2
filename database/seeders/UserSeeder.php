<?php

namespace Database\Seeders;

use App\Models\Auth\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $admin = User::create([
            'name'     => 'Admin Dr Shield',
            'email'    => 'admin@drshield.com',
            'username' => 'admin123',
            'password' => Hash::make('12345678')
        ]);

        $admin->assignRole('admin');
    }
}