<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;


class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        User::create([
            'name' => '管理者',
            'email' => 'admin@example.com',
            'password' => Hash::make('admin001'),
            'admin_status' => 'admin',
            'email_verified_at' => now(),
        ]);

        User::create([
            'name' => '山田　花子',
            'email' => 'user1@example.com',
            'password' => Hash::make('password1'),
            'email_verified_at' => null,
        ]);

        User::create([
            'name' => '鈴木　一郎',
            'email' => 'user2@example.com',
            'password' => Hash::make('password2'),
            'email_verified_at' => now(),
        ]);

        User::create([
            'name' => '高橋　美咲',
            'email' => 'user3@example.com',
            'password' => Hash::make('password3'),
            'email_verified_at' => now(),
        ]);

        User::create([
            'name' => '田中　誠',
            'email' => 'user4@example.com',
            'password' => Hash::make('password4'),
            'email_verified_at' => now(),
        ]);

        User::create([
            'name' => '近藤　美玲',
            'email' => 'user5@example.com',
            'password' => Hash::make('password5'),
            'email_verified_at' => now(),
        ]);
    }
}
