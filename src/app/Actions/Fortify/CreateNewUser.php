<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    /**
     * 新規ユーザーを作成する
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'name'     => ['required', 'string', 'max:20'],
            'email'    => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'max:72', 'confirmed'],
        ], [
            'name.required'      => 'お名前を入力してください',
            'name.max'           => 'お名前は20文字以内で入力してください',
            'email.required'     => 'メールアドレスを入力してください',
            'email.email'        => 'メールアドレスはメール形式で入力してください',
            'email.max'          => 'メールアドレスは255文字以内で入力してください',
            'email.unique'       => 'このメールアドレスは既に登録されています',
            'password.required'  => 'パスワードを入力してください',
            'password.min'       => 'パスワードは8文字以上で入力してください',
            'password.max'       => 'パスワードは72文字以内で入力してください',
            'password.confirmed' => 'パスワードと一致しません',
        ])->validate();

        return User::create([
            'name'     => $input['name'],
            'email'    => $input['email'],
            'password' => Hash::make($input['password']),
        ]);
    }
}