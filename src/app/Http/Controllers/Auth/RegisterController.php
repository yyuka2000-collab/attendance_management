<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class RegisterController extends Controller
{
    /**
     * 会員登録画面を表示する
     */
    public function showRegistrationForm()
    {
        return view('auth.register');
    }

    /**
     * 会員登録処理
     */
    public function register(RegisterRequest $request)
    {
        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
        ]);

        Auth::login($user);

        // FN005: 会員登録直後は打刻画面へ遷移（メール認証なしの場合）
        return redirect('/attendance');
    }

    /**
     * ログイン画面を表示する（動線用）
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }
}