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

            {{-- バリデーションエラー --}}
            @if ($errors->any())
                @php $shownErrors = []; @endphp
                <ul class="detail__errors">
                    @foreach ($errors->all() as $error)
                        @if (!isset($shownErrors[$error]))
                            <li class="detail__error-item">{{ $error }}</li>
                            @php $shownErrors[$error] = true; @endphp
                        @endif
                    @endforeach
                </ul>
            @endif

            @if ($isPending)
                {{-- ========================================
                     承認待ち：最新申請内容をテキスト表示
                     ======================================== --}}
                <div class="detail__form">

                    <div class="detail__row">
                        <div class="detail__label">名前</div>
                        <div class="detail__value">{{ $attendance->user->name }}</div>
                    </div>

                    <div class="detail__row">
                        <div class="detail__label">日付</div>
                        <div class="detail__value detail__value--date">
                            <span class="detail__date-year">{{ \Carbon\Carbon::parse($attendance->work_date)->format('Y年') }}</span>
                            <span class="detail__date-day">{{ \Carbon\Carbon::parse($attendance->work_date)->format('n月j日') }}</span>
                        </div>
                    </div>

                    {{-- 出勤・退勤（最新申請の値） --}}
                    <div class="detail__row">
                        <div class="detail__label">出勤・退勤</div>
                        <div class="detail__value detail__value--time-range">
                            <span class="detail__time-text">{{ $latestRequest->new_clock_in ? \Carbon\Carbon::parse($latestRequest->new_clock_in)->format('H:i') : '' }}</span>
                            <span class="detail__tilde">〜</span>
                            <span class="detail__time-text">{{ $latestRequest->new_clock_out ? \Carbon\Carbon::parse($latestRequest->new_clock_out)->format('H:i') : '' }}</span>
                        </div>
                    </div>

                    {{-- 休憩（最新申請の値） --}}
                    @foreach ($latestRequest->correctionRestTimes as $index => $rest)
                        <div class="detail__row">
                            <div class="detail__label">休憩{{ $index > 0 ? $index + 1 : '' }}</div>
                            <div class="detail__value detail__value--time-range">
                                <span class="detail__time-text">{{ $rest->rest_start ? \Carbon\Carbon::parse($rest->rest_start)->format('H:i') : '' }}</span>
                                <span class="detail__tilde">〜</span>
                                <span class="detail__time-text">{{ $rest->rest_end ? \Carbon\Carbon::parse($rest->rest_end)->format('H:i') : '' }}</span>
                            </div>
                        </div>
                    @endforeach

                    {{-- 備考（最新申請の値） --}}
                    <div class="detail__row">
                        <div class="detail__label">備考</div>
                        <div class="detail__value">{{ $latestRequest->remarks }}</div>
                    </div>

                </div>

                <p class="detail__pending-message">※承認待ちのため修正はできません。</p>

            @else
                {{-- ========================================
                     管理者：直接修正フォーム（承認フロー不要）
                     申請がある場合はその値、ない場合は打刻実値を初期値に
                     ======================================== --}}
                <form id="detail-form" action="/admin/attendance/{{ $attendance->id }}" method="POST" class="detail__form">
                    @csrf

                    <div class="detail__row">
                        <div class="detail__label">名前</div>
                        <div class="detail__value">{{ $attendance->user->name }}</div>
                    </div>

                    <div class="detail__row">
                        <div class="detail__label">日付</div>
                        <div class="detail__value detail__value--date">
                            <span class="detail__date-year">{{ \Carbon\Carbon::parse($attendance->work_date)->format('Y年') }}</span>
                            <span class="detail__date-day">{{ \Carbon\Carbon::parse($attendance->work_date)->format('n月j日') }}</span>
                        </div>
                    </div>

                    {{-- 出勤・退勤 --}}
                    @php
                        $clockIn  = $latestRequest
                            ? ($latestRequest->new_clock_in  ? \Carbon\Carbon::parse($latestRequest->new_clock_in)->format('H:i')  : '')
                            : ($attendance->clock_in         ? \Carbon\Carbon::parse($attendance->clock_in)->format('H:i')         : '');
                        $clockOut = $latestRequest
                            ? ($latestRequest->new_clock_out ? \Carbon\Carbon::parse($latestRequest->new_clock_out)->format('H:i') : '')
                            : ($attendance->clock_out        ? \Carbon\Carbon::parse($attendance->clock_out)->format('H:i')        : '');
                    @endphp
                    <div class="detail__row">
                        <div class="detail__label">出勤・退勤</div>
                        <div class="detail__value detail__value--time-range">
                            <input
                                type="text"
                                name="clock_in"
                                class="detail__time-input @error('clock_in') detail__time-input--error @enderror"
                                value="{{ old('clock_in', $clockIn) }}"
                            />
                            <span class="detail__tilde">〜</span>
                            <input
                                type="text"
                                name="clock_out"
                                class="detail__time-input @error('clock_out') detail__time-input--error @enderror"
                                value="{{ old('clock_out', $clockOut) }}"
                            />
                        </div>
                    </div>

                    {{-- 休憩 --}}
                    @php
                        $restSources = $latestRequest
                            ? $latestRequest->correctionRestTimes
                            : $attendance->restTimes;
                    @endphp
                    @foreach ($restSources as $index => $rest)
                        <div class="detail__row">
                            <div class="detail__label">休憩{{ $index > 0 ? $index + 1 : '' }}</div>
                            <div class="detail__value detail__value--time-range">
                                <input
                                    type="text"
                                    name="rest_times[{{ $index }}][rest_start]"
                                    class="detail__time-input @error('rest_times.'.$index.'.rest_start') detail__time-input--error @enderror"
                                    value="{{ old('rest_times.'.$index.'.rest_start', $rest->rest_start ? \Carbon\Carbon::parse($rest->rest_start)->format('H:i') : '') }}"
                                />
                                <span class="detail__tilde">〜</span>
                                <input
                                    type="text"
                                    name="rest_times[{{ $index }}][rest_end]"
                                    class="detail__time-input @error('rest_times.'.$index.'.rest_end') detail__time-input--error @enderror"
                                    value="{{ old('rest_times.'.$index.'.rest_end', $rest->rest_end ? \Carbon\Carbon::parse($rest->rest_end)->format('H:i') : '') }}"
                                />
                            </div>
                        </div>
                    @endforeach

                    {{-- 追加入力フィールド（空欄1つ） --}}
                    @php $newIndex = count($restSources); @endphp
                    <div class="detail__row">
                        <div class="detail__label">休憩{{ $newIndex > 0 ? $newIndex + 1 : '' }}</div>
                        <div class="detail__value detail__value--time-range">
                            <input
                                type="text"
                                name="rest_times[{{ $newIndex }}][rest_start]"
                                class="detail__time-input @error('rest_times.'.$newIndex.'.rest_start') detail__time-input--error @enderror"
                                value="{{ old('rest_times.'.$newIndex.'.rest_start', '') }}"
                            />
                            <span class="detail__tilde">〜</span>
                            <input
                                type="text"
                                name="rest_times[{{ $newIndex }}][rest_end]"
                                class="detail__time-input @error('rest_times.'.$newIndex.'.rest_end') detail__time-input--error @enderror"
                                value="{{ old('rest_times.'.$newIndex.'.rest_end', '') }}"
                            />
                        </div>
                    </div>

                    {{-- 備考 --}}
                    @php
                        $remarks = $latestRequest
                            ? ($latestRequest->remarks ?? '')
                            : ($attendance->remarks ?? '');
                    @endphp
                    <div class="detail__row detail__row--remarks">
                        <div class="detail__label">備考</div>
                        <div class="detail__value">
                            <textarea
                                name="remarks"
                                class="detail__remarks @error('remarks') detail__remarks--error @enderror"
                                rows="3"
                            >{{ old('remarks', $remarks) }}</textarea>
                        </div>
                    </div>

                </form>

                <div class="detail__actions">
                    <button type="submit" form="detail-form" class="detail__submit-button">修正</button>
                </div>

            @endif

        </div>
    </main>
</body>

</html>