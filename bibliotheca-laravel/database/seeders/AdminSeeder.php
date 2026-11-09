<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // Pass the password as plain text: the 'hashed' cast in
        // User.php hashes it automatically.
        // updateOrCreate means running this twice won't create a duplicate.
        User::updateOrCreate(
            ['username' => 'admin'],
            [
                'password' => 'admin123',
                'role'     => 'admin',
                'status'   => 'active',
            ]
        );
    }
}