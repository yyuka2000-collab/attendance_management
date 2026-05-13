<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // 管理者ユーザー
        User::create([
            'name'     => '管理者',
            'email'    => 'admin@example.com',
            'password' => Hash::make('adminpassword'),
            'role'     => 1,
        ]);

        // 一般ユーザー
        $users = [
            ['name' => '山田 太郎', 'email' => 'yamada@example.com'],
            ['name' => '佐藤 花子', 'email' => 'sato@example.com'],
            ['name' => '鈴木 一郎', 'email' => 'suzuki@example.com'],
            ['name' => '田中 美咲', 'email' => 'tanaka@example.com'],
            ['name' => '伊藤 健太', 'email' => 'ito@example.com'],
        ];

        foreach ($users as $user) {
            User::create([
                'name'     => $user['name'],
                'email'    => $user['email'],
                'password' => Hash::make('password'),
                'role'     => 0,
            ]);
        }
    }
}
