<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>勤怠</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/attendance.css') }}" />
</head>

<body>
    <header class="header">
        <a href="/" class="header__logo">
            <img src="{{ asset('img/COACHTECH_logo.png') }}" alt="COACHTECH" />
        </a>
        <nav class="header__nav">
            @if ($status === 'done')
                {{-- 退勤済のときのナビ --}}
                <a href="/attendance/list" class="header__nav-item">今月の出勤一覧</a>
                <a href="/stamp_correction_request/list" class="header__nav-item">申請一覧</a>
            @else
                {{-- 通常のナビ --}}
                <a href="/attendance" class="header__nav-item">勤怠</a>
                <a href="/attendance/list" class="header__nav-item">勤怠一覧</a>
                <a href="/stamp_correction_request/list" class="header__nav-item">申請</a>
            @endif
            <form action="/logout" method="POST" class="header__logout-form">
                @csrf
                <button type="submit" class="header__nav-item header__nav-item--logout">ログアウト</button>
            </form>
        </nav>
    </header>

    <main>
        <div class="attendance__content">

            {{-- ステータスバッジ --}}
            <div class="attendance__status">
                <span class="attendance__status-badge attendance__status-badge--{{ $status }}">
                    @if ($status === 'off')
                        勤務外
                    @elseif ($status === 'working')
                        出勤中
                    @elseif ($status === 'break')
                        休憩中
                    @elseif ($status === 'done')
                        退勤済
                    @endif
                </span>
            </div>

            {{-- 日時表示 --}}
            <div class="attendance__date">
                {{ $currentDate }}
            </div>
            <div class="attendance__time" id="attendance-clock">
                {{ $currentTime }}
            </div>

            {{-- ボタン --}}
            <div class="attendance__actions">

                @if ($status === 'off')
                    {{-- 出勤ボタン --}}
                    <form action="/attendance/start" method="POST">
                        @csrf
                        <button type="submit" class="attendance__button attendance__button--primary">出 勤</button>
                    </form>

                @elseif ($status === 'working')
                    {{-- 退勤・休憩入ボタン（横並び） --}}
                    <div class="attendance__actions--row">
                        <form action="/attendance/end" method="POST">
                            @csrf
                            <button type="submit" class="attendance__button attendance__button--primary">退 勤</button>
                        </form>
                        <form action="/attendance/break-start" method="POST">
                            @csrf
                            <button type="submit" class="attendance__button attendance__button--secondary">休憩入</button>
                        </form>
                    </div>

                @elseif ($status === 'break')
                    {{-- 休憩戻ボタン --}}
                    <form action="/attendance/break-end" method="POST">
                        @csrf
                        <button type="submit" class="attendance__button attendance__button--secondary">休憩戻</button>
                    </form>

                @elseif ($status === 'done')
                    {{-- 退勤済メッセージ --}}
                    <p class="attendance__done-message">お疲れ様でした。</p>

                @endif

            </div>

        </div>
    </main>

    <script>
        // リアルタイム時計（秒単位で更新）
        function updateClock() {
            const now = new Date();
            const h = String(now.getHours()).padStart(2, '0');
            const m = String(now.getMinutes()).padStart(2, '0');
            const el = document.getElementById('attendance-clock');
            if (el) el.textContent = h + ':' + m;
        }
        setInterval(updateClock, 1000);
    </script>
</body>

</html>