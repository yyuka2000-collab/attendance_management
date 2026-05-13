<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Http\Responses\LoginResponse;
use App\Http\Responses\LogoutResponse;
use App\Http\Responses\RegisterResponse;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Contracts\LogoutResponse as LogoutResponseContract;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\Features;


class FortifyServiceProvider extends ServiceProvider
{
    /**
     * サービスの登録
     */
    public function register(): void
    {
        $this->app->singleton(LoginResponseContract::class, LoginResponse::class);
        $this->app->singleton(LogoutResponseContract::class, LogoutResponse::class);
        $this->app->singleton(RegisterResponseContract::class, RegisterResponse::class);
    }

    /**
     * サービスの起動
     */
    public function boot(): void
    {

        // 会員登録アクションの登録
        Fortify::createUsersUsing(CreateNewUser::class);

        // 会員登録画面
        Fortify::registerView(function () {
            return view('auth.register');
        });

        // ログイン画面（管理者と一般ユーザーで切り替え）
        Fortify::loginView(function () {
            if (request()->is('admin/*')) {
                return view('admin.auth.login');
            }
            return view('auth.login');
        });

        // 認証誘導画面
        Fortify::verifyEmailView(function () {
            return view('auth.verify-email');
        });

        // ログイン認証処理
        Fortify::authenticateUsing(function (Request $request) {
            $user = User::where('email', $request->email)->first();

            if ($user && Hash::check($request->password, $user->password)) {
                return $user;
            }
        });

        // レート制限を無効化
        RateLimiter::for('login', function (Request $request) {
            return Limit::none();
        });
    }
}