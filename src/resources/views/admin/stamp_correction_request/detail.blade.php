<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>勤怠詳細</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/attendance.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/attendance-detail.css') }}" />
</head>

<body>
    <header class="header">
        <a href="/" class="header__logo">
            <img src="{{ asset('img/COACHTECH_logo.png') }}" alt="COACHTECH" />
        </a>
        <nav class="header__nav">
            <a href="/admin/attendance/list" class="header__nav-item">勤怠一覧</a>
            <a href="/admin/staff/list" class="header__nav-item">スタッフ一覧</a>
            <a href="/stamp_correction_request/list" class="header__nav-item">申請一覧</a>
            <form action="/admin/logout" method="POST" class="header__logout-form">
                @csrf
                <button type="submit" class="header__nav-item header__nav-item--logout">ログアウト</button>
            </form>
        </nav>
    </header>

    <main class="detail-main">
        <div class="detail__container">

            {{-- ページタイトル --}}
            <h1 class="detail__title">勤怠詳細</h1>

            {{-- 申請内容をテキスト表示（編集不可） --}}
            <div class="detail__form">

                {{-- 名前 --}}
                <div class="detail__row">
                    <div class="detail__label">名前</div>
                    <div class="detail__value">{{ $correctionRequest->user->name }}</div>
                </div>

                {{-- 日付 --}}
                <div class="detail__row">
                    <div class="detail__label">日付</div>
                    <div class="detail__value detail__value--date">
                        <span class="detail__date-year">
                            {{ \Carbon\Carbon::parse($correctionRequest->attendance->work_date)->format('Y年') }}
                        </span>
                        <span class="detail__date-day">
                            {{ \Carbon\Carbon::parse($correctionRequest->attendance->work_date)->format('n月j日') }}
                        </span>
                    </div>
                </div>

                {{-- 出勤・退勤 --}}
                <div class="detail__row">
                    <div class="detail__label">出勤・退勤</div>
                    <div class="detail__value detail__value--time-range">
                        <span class="detail__time-text">
                            {{ $correctionRequest->new_clock_in
                                ? \Carbon\Carbon::parse($correctionRequest->new_clock_in)->format('H:i')
                                : '--:--' }}
                        </span>
                        <span class="detail__tilde">〜</span>
                        <span class="detail__time-text">
                            {{ $correctionRequest->new_clock_out
                                ? \Carbon\Carbon::parse($correctionRequest->new_clock_out)->format('H:i')
                                : '--:--' }}
                        </span>
                    </div>
                </div>

                {{-- 休憩 --}}
                @forelse ($correctionRequest->correctionRestTimes as $index => $rest)
                    <div class="detail__row">
                        <div class="detail__label">休憩{{ $index === 0 ? '' : $index + 1 }}</div>
                        <div class="detail__value detail__value--time-range">
                            <span class="detail__time-text">
                                {{ $rest->rest_start
                                    ? \Carbon\Carbon::parse($rest->rest_start)->format('H:i')
                                    : '--:--' }}
                            </span>
                            <span class="detail__tilde">〜</span>
                            <span class="detail__time-text">
                                {{ $rest->rest_end
                                    ? \Carbon\Carbon::parse($rest->rest_end)->format('H:i')
                                    : '--:--' }}
                            </span>
                        </div>
                    </div>
                @empty
                    <div class="detail__row">
                        <div class="detail__label">休憩</div>
                        <div class="detail__value">--:-- 〜 --:--</div>
                    </div>
                @endforelse

                {{-- 備考 --}}
                <div class="detail__row">
                    <div class="detail__label">備考</div>
                    <div class="detail__value">{{ $correctionRequest->remarks }}</div>
                </div>

            </div>

            {{-- 承認ボタン / 承認済みボタン --}}
            <div class="detail__actions">
                @if ($correctionRequest->status === 0)
                    <form action="{{ route('admin.stamp_correction_request.approve', ['attendance_correct_request_id' => $correctionRequest->id]) }}" method="POST">
                        @csrf
                        <button type="submit" class="detail__submit-button">承認</button>
                    </form>
                @else
                    <button type="button" class="detail__submit-button detail__submit-button--approved" disabled>承認済み</button>
                @endif
            </div>

        </div>
    </main>
</body>

</html>