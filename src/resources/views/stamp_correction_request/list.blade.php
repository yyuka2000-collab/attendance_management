<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>申請一覧</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/attendance.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/correction-request-list.css') }}" />
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

    <main class="request-main">
        <div class="request-container">

            {{-- ページタイトル --}}
            <h1 class="request-title">申請一覧</h1>

            {{-- タブ --}}
            <div class="request-tabs">
                <button type="button"
                        class="request-tabs__tab request-tabs__tab--active"
                        data-tab="pending">
                    承認待ち
                </button>
                <button type="button"
                        class="request-tabs__tab"
                        data-tab="approved">
                    承認済み
                </button>
            </div>

            {{-- 承認待ちテーブル --}}
            <div class="request-table-wrapper" id="tab-pending">
                <table class="request-table">
                    <thead>
                        <tr>
                            <th class="request-table__th">状態</th>
                            <th class="request-table__th">名前</th>
                            <th class="request-table__th">対象日時</th>
                            <th class="request-table__th">申請理由</th>
                            <th class="request-table__th">申請日時</th>
                            <th class="request-table__th request-table__th--detail">詳細</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($pending as $request)
                            <tr class="request-table__row">
                                <td class="request-table__td">承認待ち</td>
                                <td class="request-table__td  request-table__td--name">{{ Auth::user()->name }}</td>
                                <td class="request-table__td">
                                    {{ \Carbon\Carbon::parse($request->attendance->work_date)->format('Y/m/d') }}
                                </td>
                                <td class="request-table__td request-table__td--remarks">{{ $request->remarks }}</td>
                                <td class="request-table__td">
                                    {{ \Carbon\Carbon::parse($request->created_at)->format('Y/m/d') }}
                                </td>
                                <td class="request-table__td request-table__td--detail">
                                    <a href="{{ route('attendance.detail', ['id' => $request->attendance_id]) }}"
                                       class="request-table__detail-link">詳細</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="request-table__empty">申請はありません</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- 承認済みテーブル --}}
            <div class="request-table-wrapper" id="tab-approved" style="display: none;">
                <table class="request-table">
                    <thead>
                        <tr>
                            <th class="request-table__th">状態</th>
                            <th class="request-table__th">名前</th>
                            <th class="request-table__th">対象日時</th>
                            <th class="request-table__th">申請理由</th>
                            <th class="request-table__th">申請日時</th>
                            <th class="request-table__th request-table__th--detail">詳細</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($approved as $request)
                            <tr class="request-table__row">
                                <td class="request-table__td">承認済み</td>
                                <td class="request-table__td">{{ Auth::user()->name }}</td>
                                <td class="request-table__td">
                                    {{ \Carbon\Carbon::parse($request->attendance->work_date)->format('Y/m/d') }}
                                </td>
                                <td class="request-table__td request-table__td--remarks">{{ $request->remarks }}</td>
                                <td class="request-table__td">
                                    {{ \Carbon\Carbon::parse($request->approved_at)->format('Y/m/d') }}
                                </td>
                                <td class="request-table__td request-table__td--detail">
                                    <a href="{{ route('attendance.detail', ['id' => $request->attendance_id]) }}"
                                       class="request-table__detail-link">詳細</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="request-table__empty">申請はありません</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    </main>

    <script>
        document.querySelectorAll('.request-tabs__tab').forEach(function (tab) {
            tab.addEventListener('click', function () {
                // タブのアクティブ切り替え
                document.querySelectorAll('.request-tabs__tab').forEach(function (t) {
                    t.classList.remove('request-tabs__tab--active');
                });
                this.classList.add('request-tabs__tab--active');

                // テーブルの表示切り替え
                var target = this.dataset.tab;
                document.getElementById('tab-pending').style.display  = target === 'pending'  ? '' : 'none';
                document.getElementById('tab-approved').style.display = target === 'approved' ? '' : 'none';
            });
        });
    </script>
</body>

</html>