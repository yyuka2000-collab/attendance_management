<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>メール認証</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}">
    <link rel="stylesheet" href="{{ asset('css/verify-email.css') }}">
</head>
<body>
    <header class="header">
        <div class="header__inner">
            <a href="/" class="header__logo">
                <img src="{{ asset('img/COACHTECH_logo.png') }}" alt="COACHTECH">
            </a>
        </div>
    </header>

    <main class="main">
        <div class="verify">
            <p class="verify__message">
                登録していただいたメールアドレスに認証メールを送付しました。<br>
                メール認証を完了してください。
            </p>

            <a href="http://localhost:8025" class="verify__button" target="_blank">
                認証はこちらから
            </a>

            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <button type="submit" class="verify__resend">
                    認証メールを再送する
                </button>
            </form>
        </div>
    </main>
</body>
</html>