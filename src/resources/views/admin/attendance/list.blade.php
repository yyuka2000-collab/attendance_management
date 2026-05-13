<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>勤怠一覧（管理者）</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/attendance.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/admin-attendance-list.css') }}" />
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

    <main class="list-main">
        <div class="list-container">

            {{-- ページタイトル --}}
            <div class="list-title-row">
                <h1 class="list-title">{{ $currentDate }}の勤怠</h1>
            </div>

            {{-- 日付ナビゲーション --}}
            <div class="list-day-nav">
                <a href="{{ route('admin.attendance.list', ['date' => $prevDate]) }}" class="list-day-nav__arrow list-day-nav__arrow--prev">
                    &#8592; 前日
                </a>
                <div class="list-day-nav__current">
                    <span class="list-day-nav__icon">
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
                    <span class="list-day-nav__label">{{ $currentDateFormatted }}</span>
                </div>
                <a href="{{ route('admin.attendance.list', ['date' => $nextDate]) }}" class="list-day-nav__arrow list-day-nav__arrow--next">
                    翌日 &#8594;
                </a>
            </div>

            {{-- 勤怠テーブル --}}
            <div class="list-table-wrapper">
                <table class="list-table">
                    <thead>
                        <tr>
                            <th class="list-table__th list-table__th--name">名前</th>
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
                                <td class="list-table__td list-table__td--name">
                                    {{ $attendance['name'] }}
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
                                    <a href="{{ route('admin.attendance.find', [$attendance['user_id'], $attendance['date_raw']]) }}" class="list-table__detail-link">
                                            詳細
                                    </a>
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