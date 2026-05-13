<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>スタッフ一覧（管理者）</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/attendance.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/admin-staff-list.css') }}" />
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

    <main class="staff-main">
        <div class="staff-container">

            {{-- ページタイトル --}}
            <div class="staff-title-row">
                <h1 class="staff-title">スタッフ一覧</h1>
            </div>

            {{-- スタッフテーブル --}}
            <div class="staff-table-wrapper">
                <table class="staff-table">
                    <thead>
                        <tr>
                            <th class="staff-table__th staff-table__th--name">名前</th>
                            <th class="staff-table__th staff-table__th--email">メールアドレス</th>
                            <th class="staff-table__th staff-table__th--detail">月次勤怠</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($users as $user)
                            <tr class="staff-table__row">
                                <td class="staff-table__td staff-table__td--name">
                                    {{ $user->name }}
                                </td>
                                <td class="staff-table__td staff-table__td--email">
                                    {{ $user->email }}
                                </td>
                                <td class="staff-table__td staff-table__td--detail">
                                    <a href="{{ route('admin.attendance.staff', $user->id) }}" class="staff-table__detail-link">
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