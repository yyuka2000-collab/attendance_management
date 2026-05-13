<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminLoginRequest;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('admin.auth.login');
    }

    public function login(AdminLoginRequest $request)
    {
        $credentials = $request->only('email', 'password');

        if (!Auth::guard('admin')->attempt($credentials)) {
            return back()->withErrors([
                'email' => 'ログイン情報が登録されていません',
            ])->withInput();
        }

        $request->session()->regenerate();

        return redirect('/admin/attendance/list');
    }

    public function logout()
    {
        Auth::guard('admin')->logout();

        return redirect('/admin/login');
    }
}