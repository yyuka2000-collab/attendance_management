<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>会員登録</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/register.css') }}" />
</head>

<body>
    <header class="header">
        <a href="/" class="header__logo">
            <img src="{{ asset('img/COACHTECH_logo.png') }}" alt="COACHTECH" />
        </a>
    </header>

    <main>
        <div class="register-form__content">
            <div class="register-form__heading">
                <h1>会員登録</h1>
            </div>

            <form class="form" action="/register" method="POST" novalidate>
                @csrf

                <!-- 名前 -->
                <div class="form__group">
                    <div class="form__group-title">
                        <span class="form__label--item">名前</span>
                    </div>
                    <div class="form__group-content">
                        <div class="form__input--text">
                            <input type="text" name="name" value="{{ old('name') }}" />
                        </div>
                        @error('name')
                        <div class="form__error">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

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

                <!-- パスワード確認 -->
                <div class="form__group">
                    <div class="form__group-title">
                        <span class="form__label--item">パスワード確認</span>
                    </div>
                    <div class="form__group-content">
                        <div class="form__input--text">
                            <input type="password" name="password_confirmation" />
                        </div>
                        @error('password_confirmation')
                        <div class="form__error">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="form__button">
                    <button class="form__button-submit" type="submit">登録する</button>
                </div>
            </form>

            <div class="form__login-link">
                <a href="/login">ログインはこちら</a>
            </div>
        </div>
    </main>
</body>

</html>