<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>ログイン</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/login.css') }}" />
</head>

<body>
    <header class="header">
        <a href="/" class="header__logo">
            <img src="{{ asset('img/COACHTECH_logo.png') }}" alt="COACHTECH" />
        </a>
    </header>

    <main>
        <div class="login-form__content">
            <div class="login-form__heading">
                <h1>ログイン</h1>
            </div>

            <form class="form" action="/login" method="POST" novalidate>
                @csrf

                <!-- メールアドレス -->
                <div class="form__group">
                    <div class="form__group-title">
                        <span class="form__label--item">メールアドレス</span>
                    </div>
                    <div class="form__group-content">
                        <div class="form__input--text">
                            <input type="text" name="email" value="{{ old('email') }}" />
                        </div>
                        @error('email')
                        <div class="form__error">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <!-- パスワード -->
                <div class="form__group">
                    <div class="form__group-title">
                        <span class="form__label--item">パスワード</span>
                    </div>
                    <div class="form__group-content">
                        <div class="form__input--text">
                            <input type="password" name="password" />
                        </div>
                        @error('password')
                        <div class="form__error">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="form__button">
                    <button class="form__button-submit" type="submit">ログインする</button>
                </div>
            </form>

            <div class="form__register-link">
                <a href="/register">会員登録はこちら</a>
            </div>
        </div>
    </main>
</body>

</html>