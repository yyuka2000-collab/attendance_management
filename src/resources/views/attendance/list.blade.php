<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>勤怠一覧</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/attendance.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/attendance-list.css') }}" />
</head>

<body>
    <header class="header">
        <a href="/" class="header__logo">
            <img src="{{ asset('img/COACHTECH_logo.png') }}" alt="COACHTECH" />
        </a>
        <nav class="header__nav">
            <a href="/attendance" class="header__nav-item">勤怠</a>
            <a href="/attendance/list" class="header__nav-item">勤怠一覧</a>
            <a href="/stamp_correction_request/list" class="header__nav-item">申請</a>
            <form action="/logout" method="POST" class="header__logout-form">
                @csrf
                <button type="submit" class="header__nav-item header__nav-item--logout">ログアウト</button>
            </form>
        </nav>
    </header>

    <main class="list-main">
        <div class="list-container">

            {{-- ページタイトル＋注釈 --}}
            <div class="list-title-row">
                <h1 class="list-title">勤怠一覧</h1>
                <p class="list-table__note">※は申請中のため、承認後に反映されます</p>
            </div>

            {{-- 月ナビゲーション --}}
            <div class="list-month-nav">
                <a href="{{ route('attendance.list', ['month' => $prevMonth]) }}" class="list-month-nav__arrow list-month-nav__arrow--prev">
                    &#8592; 前月
                </a>
                <div class="list-month-nav__current">
                    <span class="list-month-nav__icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                            <line x1="16" y1="2" x2="16" y2="6"/>
                            <line x1="8" y1="2" x2="8" y2="6"/>
                            <line x1="3" y1="10" x2="21" y2="10"/>
                            <line x1="8" y1="14" x2="8.01" y2="14"/>
                            <line x1="12" y1="14" x2="12.01" y2="14"/>
                            <line x1="16" y1="14" x2="16.01" y2="14"/>
                            <line x1="8" y1="18" x2="8.01" y2="18"/>
                            <line x1="12" y1="18" x2="12.01" y2="18"/>
                        </svg>
                    </span>
                    <span class="list-month-nav__label">{{ $currentMonth }}</span>
                </div>
                <a href="{{ route('attendance.list', ['month' => $nextMonth]) }}" class="list-month-nav__arrow list-month-nav__arrow--next">
                    翌月 &#8594;
                </a>
            </div>

            {{-- 勤怠テーブル --}}
            <div class="list-table-wrapper">

                <table class="list-table">
                    <thead>
                        <tr>
                            <th class="list-table__th list-table__th--date">日付</th>
                            <th class="list-table__th">出勤</th>
                            <th class="list-table__th">退勤</th>
                            <th class="list-table__th">休憩</th>
                            <th class="list-table__th">合計</th>
                            <th class="list-table__th list-table__th--detail">詳細</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($attendances as $attendance)
                            <tr class="list-table__row">
                                <td class="list-table__td list-table__td--date">
                                    {{ $attendance['date'] }}
                                </td>
                                <td class="list-table__td">
                                    {{ $attendance['clock_in'] ?? '' }}
                                </td>
                                <td class="list-table__td">
                                    {{ $attendance['clock_out'] ?? '' }}
                                </td>
                                <td class="list-table__td">
                                    {{ $attendance['rest_time'] ?? '' }}
                                </td>
                                <td class="list-table__td">
                                    {{ $attendance['total_time'] ?? '' }}
                                </td>
                                <td class="list-table__td list-table__td--detail">
                                    @if ($attendance['id'])
                                        <a href="{{ route('attendance.detail', ['id' => $attendance['id']]) }}" class="list-table__detail-link">
                                            詳細
                                            @if ($attendance['is_pending'])
                                                <span class="list-table__pending-mark">※</span>
                                            @endif
                                        </a>
                                    @else
                                        <a href="#" class="list-table__detail-link">詳細</a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

        </div>
    </main>
</body>

</html>