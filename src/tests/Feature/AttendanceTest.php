<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\RestTime;
use App\Models\StampCorrectionRequest;
use Carbon\Carbon;

/**
 * 勤怠管理アプリ PHPUnit テスト
 *
 * テストケース一覧（仕様書）ID 1〜15 に準拠
 */
class AttendanceTest extends TestCase
{
    use DatabaseTransactions;

    // =========================================================
    // ヘルパー
    // =========================================================

    private function createUser(array $attrs = []): User
    {
        return User::factory()->create(array_merge([
            'role'              => 0,
            'email_verified_at' => now(),
        ], $attrs));
    }

    private function createAdmin(array $attrs = []): User
    {
        return User::factory()->create(array_merge([
            'role'              => 1,
            'email_verified_at' => now(),
        ], $attrs));
    }

    private function createAttendance(User $user, array $attrs = []): Attendance
    {
        return Attendance::factory()->create(array_merge([
            'user_id'   => $user->id,
            'work_date' => Carbon::today(),
        ], $attrs));
    }

    // =========================================================
    // ID:1 認証機能（一般ユーザー）
    // =========================================================

    /**
     * @test
     * 名前が未入力の場合、バリデーションメッセージが表示される
     * 期待挙動：「お名前を入力してください」というバリデーションメッセージが表示される
     */
    public function test_register_name_required_shows_validation_message(): void
    {
        $response = $this->post('/register', [
            'name'                  => '',
            'email'                 => uniqid('test_') . '@example.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors([
            'name' => 'お名前を入力してください',
        ]);
    }

    /**
     * @test
     * メールアドレスが未入力の場合、バリデーションメッセージが表示される
     * 期待挙動：「メールアドレスを入力してください」というバリデーションメッセージが表示される
     */
    public function test_register_email_required_shows_validation_message(): void
    {
        $response = $this->post('/register', [
            'name'                  => 'テストユーザー',
            'email'                 => '',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ]);


        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください',
        ]);
    }

    /**
     * @test
     * パスワードが8文字未満の場合、バリデーションメッセージが表示される
     * 期待挙動：「パスワードは8文字以上で入力してください」というバリデーションメッセージが表示される
     */
    public function test_register_password_less_than_8_chars_shows_validation_message(): void
    {
        $response = $this->post('/register', [
            'name'                  => 'テストユーザー',
            'email'                 => uniqid('test_') . '@example.com',
            'password'              => 'short',
            'password_confirmation' => 'short',
        ]);

        $response->assertSessionHasErrors([
            'password' => 'パスワードは8文字以上で入力してください',
        ]);
    }

    /**
     * @test
     * パスワードが一致しない場合、バリデーションメッセージが表示される
     * 期待挙動：「パスワードと一致しません」というバリデーションメッセージが表示される
     */
    public function test_register_password_confirmation_mismatch_shows_validation_message(): void
    {
        $response = $this->post('/register', [
            'name'                  => 'テストユーザー',
            'email'                 => uniqid('test_') . '@example.com',
            'password'              => 'password123',
            'password_confirmation' => 'different456',
        ]);

        $response->assertSessionHasErrors([
            'password' => 'パスワードと一致しません',
        ]);
    }

    /**
     * @test
     * パスワードが未入力の場合、バリデーションメッセージが表示される
     * 期待挙動：「パスワードを入力してください」というバリデーションメッセージが表示される
     */
    public function test_register_password_required_shows_validation_message(): void
    {
        $response = $this->post('/register', [
            'name'                  => 'テストユーザー',
            'email'                 => uniqid('test_') . '@example.com',
            'password'              => '',
            'password_confirmation' => '',
        ]);

        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください',
        ]);
    }

    /**
     * @test
     * フォームに内容が入力されていた場合、データが正常に保存される
     * 期待挙動：データベースに登録したユーザー情報が保存される
     */
    public function test_register_with_valid_data_saves_user_to_database(): void
    {
        $email = uniqid('test_') . '@example.com';

        $this->post('/register', [
            'name'                  => 'テストユーザー',
            'email'                 => $email,
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $this->assertDatabaseHas('users', [
            'name'  => 'テストユーザー',
            'email' => $email,
        ]);
    }

    // =========================================================
    // ID:2 ログイン認証機能（一般ユーザー）
    // =========================================================

    /**
     * @test
     * メールアドレスが未入力の場合、バリデーションメッセージが表示される
     * 期待挙動：「メールアドレスを入力してください」というバリデーションメッセージが表示される
     */
    public function test_user_login_email_required_shows_validation_message(): void
    {
        $this->createUser();

        $response = $this->post('/login', [
            'email'    => '',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください',
        ]);
    }

    /**
     * @test
     * パスワードが未入力の場合、バリデーションメッセージが表示される
     * 期待挙動：「パスワードを入力してください」というバリデーションメッセージが表示される
     */
    public function test_user_login_password_required_shows_validation_message(): void
    {
        $user = $this->createUser();

        $response = $this->post('/login', [
            'email'    => $user->email,
            'password' => '',
        ]);

        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください',
        ]);
    }

    /**
     * @test
     * 登録内容と一致しない場合、バリデーションメッセージが表示される
     * 期待挙動：「ログイン情報が登録されていません」というバリデーションメッセージが表示される
     */
    public function test_user_login_wrong_credentials_shows_validation_message(): void
    {
        $this->createUser(['email' => uniqid('correct_') . '@example.com']);

        $response = $this->post('/login', [
            'email'    => uniqid('wrong_') . '@example.com',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors(['email']);
        $errors = session('errors');
        $this->assertStringContainsString(
            'ログイン情報が登録されていません',
            $errors->first('email')
        );
    }

    // =========================================================
    // ID:3 ログイン認証機能（管理者）
    // =========================================================

    /**
     * @test
     * メールアドレスが未入力の場合、バリデーションメッセージが表示される
     * 期待挙動：「メールアドレスを入力してください」というバリデーションメッセージが表示される
     */
    public function test_admin_login_email_required_shows_validation_message(): void
    {
        $this->createAdmin();

        $response = $this->post('/admin/login', [
            'email'    => '',
            'password' => 'adminpassword',
        ]);

        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください',
        ]);
    }

    /**
     * @test
     * パスワードが未入力の場合、バリデーションメッセージが表示される
     * 期待挙動：「パスワードを入力してください」というバリデーションメッセージが表示される
     */
    public function test_admin_login_password_required_shows_validation_message(): void
    {
        $admin = $this->createAdmin();

        $response = $this->post('/admin/login', [
            'email'    => $admin->email,
            'password' => '',
        ]);

        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください',
        ]);
    }

    /**
     * @test
     * 登録内容と一致しない場合、バリデーションメッセージが表示される
     * 期待挙動：「ログイン情報が登録されていません」というバリデーションメッセージが表示される
     */
    public function test_admin_login_wrong_credentials_shows_validation_message(): void
    {
        $this->createAdmin(['email' => uniqid('admin_') . '@example.com']);

        $response = $this->post('/admin/login', [
            'email'    => uniqid('wrong_') . '@example.com',
            'password' => 'adminpassword',
        ]);

        $response->assertSessionHasErrors(['email']);
        $errors = session('errors');
        $this->assertStringContainsString(
            'ログイン情報が登録されていません',
            $errors->first('email')
        );
    }

    // =========================================================
    // ID:4 日時取得機能
    // =========================================================

    /**
     * @test
     * 現在の日時情報がUIと同じ形式で出力されている
     * 期待挙動：画面上に表示されている日時が現在の日時と一致する
     */
    public function test_attendance_index_shows_current_datetime_in_correct_format(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);

        $response = $this->get('/attendance');

        $response->assertStatus(200);
        // 仕様の表示形式：YYYY年M月D日(ddd) 例「2026年5月9日(土)」
        $expectedDate = Carbon::now()->isoFormat('YYYY年M月D日(ddd)');
        $response->assertSee($expectedDate);
    }

    // =========================================================
    // ID:5 ステータス確認機能
    // =========================================================

    /**
     * @test
     * 勤務外の場合、勤怠ステータスが正しく表示される
     * 期待挙動：画面上に表示されているステータスが「勤務外」となる
     */
    public function test_attendance_status_shows_off_when_no_attendance_today(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);

        $response = $this->get('/attendance');

        $response->assertStatus(200);
        $response->assertSee('勤務外');
    }

    /**
     * @test
     * 出勤中の場合、勤怠ステータスが正しく表示される
     * 期待挙動：画面上に表示されているステータスが「出勤中」となる
     */
    public function test_attendance_status_shows_working_when_clocked_in(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);
        $this->createAttendance($user, [
            'status'   => 1,
            'clock_in' => now(),
        ]);

        $response = $this->get('/attendance');

        $response->assertStatus(200);
        $response->assertSee('出勤中');
    }

    /**
     * @test
     * 休憩中の場合、勤怠ステータスが正しく表示される
     * 期待挙動：画面上に表示されているステータスが「休憩中」となる
     */
    public function test_attendance_status_shows_on_break_when_resting(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);
        $this->createAttendance($user, [
            'status'   => 2,
            'clock_in' => now()->subHours(2),
        ]);

        $response = $this->get('/attendance');

        $response->assertStatus(200);
        $response->assertSee('休憩中');
    }

    /**
     * @test
     * 退勤済の場合、勤怠ステータスが正しく表示される
     * 期待挙動：画面上に表示されているステータスが「退勤済」となる
     */
    public function test_attendance_status_shows_done_when_clocked_out(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);
        $this->createAttendance($user, [
            'status'    => 3,
            'clock_in'  => now()->subHours(8),
            'clock_out' => now(),
        ]);

        $response = $this->get('/attendance');

        $response->assertStatus(200);
        $response->assertSee('退勤済');
    }

    // =========================================================
    // ID:6 出勤機能
    // =========================================================

    /**
     * @test
     * 出勤ボタンが正しく機能する
     * 期待挙動：「出勤」ボタンが表示され、処理後にステータスが「出勤中」になる
     */
    public function test_clock_in_button_appears_and_changes_status_to_working(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);

        // 勤務外状態で出勤ボタンが表示される
        $beforeResponse = $this->get('/attendance');
        $beforeResponse->assertSee('出 勤');

        // 出勤処理
        $this->post('/attendance/start');

        // ステータスが出勤中になる
        $afterResponse = $this->get('/attendance');
        $afterResponse->assertSee('出勤中');
    }

    /**
     * @test
     * 出勤は一日一回のみできる
     * 期待挙動：退勤済ユーザーの画面上に「出勤」ボタンが表示されない
     */
    public function test_clock_in_button_not_shown_when_already_clocked_out(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);
        $this->createAttendance($user, [
            'status'    => 3,
            'clock_in'  => now()->subHours(8),
            'clock_out' => now(),
        ]);

        $response = $this->get('/attendance');

        $response->assertStatus(200);
        $response->assertDontSee('出 勤');
    }

    /**
     * @test
     * 出勤時刻が勤怠一覧画面で確認できる
     * 期待挙動：勤怠一覧画面に出勤時刻が正確に記録されている
     */
    public function test_clock_in_time_is_shown_on_attendance_list(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);

        $this->post('/attendance/start');

        $clockInTime = Carbon::now()->format('H:i');
        $response    = $this->get('/attendance/list');

        $response->assertStatus(200);
        $response->assertSee($clockInTime);
    }

    // =========================================================
    // ID:7 休憩機能
    // =========================================================

    /**
     * @test
     * 休憩ボタンが正しく機能する
     * 期待挙動：「休憩入」ボタンが表示され、処理後にステータスが「休憩中」になる
     */
    public function test_break_start_button_appears_and_changes_status_to_on_break(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);
        $this->createAttendance($user, [
            'status'   => 1,
            'clock_in' => now()->subHours(1),
        ]);

        // 出勤中状態で休憩入ボタンが表示される
        $beforeResponse = $this->get('/attendance');
        $beforeResponse->assertSee('休憩入');

        // 休憩入処理
        $this->post('/attendance/break-start');

        // ステータスが休憩中になる
        $afterResponse = $this->get('/attendance');
        $afterResponse->assertSee('休憩中');
    }

    /**
     * @test
     * 休憩は一日に何回でもできる
     * 期待挙動：休憩入・休憩戻を繰り返した後も「休憩入」ボタンが表示される
     */
    public function test_break_can_be_taken_multiple_times_per_day(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);
        $this->createAttendance($user, [
            'status'   => 1,
            'clock_in' => now()->subHours(3),
        ]);

        // 1回目の休憩を完了
        $this->post('/attendance/break-start');
        $this->post('/attendance/break-end');

        // 出勤中に戻り、再び休憩入ボタンが表示される
        $response = $this->get('/attendance');
        $response->assertSee('休憩入');
    }

    /**
     * @test
     * 休憩戻ボタンが正しく機能する
     * 期待挙動：休憩戻ボタンが表示され、処理後にステータスが「出勤中」に変更される
     */
    public function test_break_end_button_appears_and_changes_status_back_to_working(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);
        $attendance = $this->createAttendance($user, [
            'status'   => 2,
            'clock_in' => now()->subHours(2),
        ]);
        RestTime::create([
            'attendance_id' => $attendance->id,
            'rest_start'    => now()->subMinutes(30),
            'rest_end'      => null,
        ]);

        // 休憩中状態で休憩戻ボタンが表示される
        $beforeResponse = $this->get('/attendance');
        $beforeResponse->assertSee('休憩戻');

        // 休憩戻処理
        $this->post('/attendance/break-end');

        // ステータスが出勤中に戻る
        $afterResponse = $this->get('/attendance');
        $afterResponse->assertSee('出勤中');
    }

    /**
     * @test
     * 休憩戻は一日に何回でもできる
     * 期待挙動：休憩入・休憩戻を繰り返した後も「休憩戻」ボタンが表示される
     */
    public function test_break_end_can_be_done_multiple_times_per_day(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);
        $this->createAttendance($user, [
            'status'   => 1,
            'clock_in' => now()->subHours(4),
        ]);

        // 1回目の休憩を完了
        $this->post('/attendance/break-start');
        $this->post('/attendance/break-end');

        // 2回目の休憩入
        $this->post('/attendance/break-start');

        // 休憩戻ボタンが表示される
        $response = $this->get('/attendance');
        $response->assertSee('休憩戻');
    }

    /**
     * @test
     * 休憩時刻が勤怠一覧画面で確認できる
     * 期待挙動：勤怠一覧画面に休憩時刻が正確に記録されている
     */
    public function test_break_time_is_shown_on_attendance_list(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);
        $attendance = $this->createAttendance($user, [
            'status'   => 3,
            'clock_in'  => now()->subHours(3),
            'clock_out' => now(),
        ]);
        RestTime::create([
            'attendance_id' => $attendance->id,
            'rest_start'    => now()->subHours(2),
            'rest_end'      => now()->subHours(1),
        ]);

        $response = $this->get('/attendance/list');

        $response->assertStatus(200);
        // 1時間（1:00）の休憩が表示される
        $response->assertSee('1:00');
    }

    // =========================================================
    // ID:8 退勤機能
    // =========================================================

    /**
     * @test
     * 退勤ボタンが正しく機能する
     * 期待挙動：「退勤」ボタンが表示され、処理後にステータスが「退勤済」になる
     */
    public function test_clock_out_button_appears_and_changes_status_to_done(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);
        $this->createAttendance($user, [
            'status'   => 1,
            'clock_in' => now()->subHours(8),
        ]);

        // 出勤中状態で退勤ボタンが表示される
        $beforeResponse = $this->get('/attendance');
        $beforeResponse->assertSee('退 勤');

        // 退勤処理
        $this->post('/attendance/end');

        // ステータスが退勤済になり「お疲れ様でした。」が表示される
        $afterResponse = $this->get('/attendance');
        $afterResponse->assertSee('退勤済');
        $afterResponse->assertSee('お疲れ様でした。');
    }

    /**
     * @test
     * 退勤時刻が勤怠一覧画面で確認できる
     * 期待挙動：勤怠一覧画面に退勤時刻が正確に記録されている
     */
    public function test_clock_out_time_is_shown_on_attendance_list(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);

        $this->post('/attendance/start');
        $this->post('/attendance/end');

        $clockOutTime = Carbon::now()->format('H:i');
        $response     = $this->get('/attendance/list');

        $response->assertStatus(200);
        $response->assertSee($clockOutTime);
    }

    // =========================================================
    // ID:9 勤怠一覧情報取得機能（一般ユーザー）
    // =========================================================

    /**
     * @test
     * 自分が行った勤怠情報が全て表示されている
     * 期待挙動：自分の勤怠情報が全て表示されている
     */
    public function test_attendance_list_shows_all_own_attendance_records(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);
        $this->createAttendance($user, [
            'status'    => 3,
            'clock_in'  => Carbon::today()->setTime(9, 0),
            'clock_out' => Carbon::today()->setTime(18, 0),
        ]);

        $response = $this->get('/attendance/list');

        $response->assertStatus(200);
        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }

    /**
     * @test
     * 勤怠一覧画面に遷移した際に現在の月が表示される
     * 期待挙動：現在の月が表示されている
     */
    public function test_attendance_list_shows_current_month_on_load(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);

        $response = $this->get('/attendance/list');

        $response->assertStatus(200);
        $response->assertSee(Carbon::now()->format('Y/m'));
    }

    /**
     * @test
     * 「前月」を押下した時に表示月の前月の情報が表示される
     * 期待挙動：前月の情報が表示されている
     */
    public function test_attendance_list_shows_previous_month_when_prev_param_given(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);

        $prevMonth = Carbon::now()->subMonth()->format('Y-m');
        $response  = $this->get('/attendance/list?month=' . $prevMonth);

        $response->assertStatus(200);
        $response->assertSee(Carbon::now()->subMonth()->format('Y/m'));
    }

    /**
     * @test
     * 「翌月」を押下した時に表示月の翌月の情報が表示される
     * 期待挙動：翌月の情報が表示されている
     */
    public function test_attendance_list_shows_next_month_when_next_param_given(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);

        $nextMonth = Carbon::now()->addMonth()->format('Y-m');
        $response  = $this->get('/attendance/list?month=' . $nextMonth);

        $response->assertStatus(200);
        $response->assertSee(Carbon::now()->addMonth()->format('Y/m'));
    }

    /**
     * @test
     * 「詳細」を押下すると、その日の勤怠詳細画面に遷移する
     * 期待挙動：その日の勤怠詳細画面に遷移する
     */
    public function test_attendance_list_detail_link_navigates_to_detail_page(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);
        $attendance = $this->createAttendance($user, [
            'status'    => 3,
            'clock_in'  => Carbon::today()->setTime(9, 0),
            'clock_out' => Carbon::today()->setTime(18, 0),
        ]);

        $response = $this->get("/attendance/detail/{$attendance->id}");

        $response->assertStatus(200);
    }

    // =========================================================
    // ID:10 勤怠詳細情報取得機能（一般ユーザー）
    // =========================================================

    /**
     * @test
     * 勤怠詳細画面の「名前」がログインユーザーの氏名になっている
     * 期待挙動：名前がログインユーザーの名前になっている
     */
    public function test_attendance_detail_shows_login_users_name(): void
    {
        $user = $this->createUser(['name' => '山田 太郎']);
        $this->actingAs($user);
        $attendance = $this->createAttendance($user, [
            'status'    => 3,
            'clock_in'  => Carbon::today()->setTime(9, 0),
            'clock_out' => Carbon::today()->setTime(18, 0),
        ]);

        $response = $this->get("/attendance/detail/{$attendance->id}");

        $response->assertStatus(200);
        $response->assertSee('山田 太郎');
    }

    /**
     * @test
     * 勤怠詳細画面の「日付」が選択した日付になっている
     * 期待挙動：日付が選択した日付になっている
     */
    public function test_attendance_detail_shows_correct_work_date(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);
        $attendance = $this->createAttendance($user, [
            'status'    => 3,
            'clock_in'  => Carbon::today()->setTime(9, 0),
            'clock_out' => Carbon::today()->setTime(18, 0),
        ]);

        $response = $this->get("/attendance/detail/{$attendance->id}");

        $response->assertStatus(200);
        $response->assertSee(Carbon::today()->format('Y年'));
        $response->assertSee(Carbon::today()->isoFormat('M月D日'));
    }

    /**
     * @test
     * 「出勤・退勤」にて記されている時間がログインユーザーの打刻と一致している
     * 期待挙動：「出勤・退勤」の時間がログインユーザーの打刻と一致している
     */
    public function test_attendance_detail_shows_correct_clock_in_and_out(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);
        $attendance = $this->createAttendance($user, [
            'status'    => 3,
            'clock_in'  => Carbon::today()->setTime(9, 0),
            'clock_out' => Carbon::today()->setTime(18, 0),
        ]);

        $response = $this->get("/attendance/detail/{$attendance->id}");

        $response->assertStatus(200);
        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }

    /**
     * @test
     * 「休憩」にて記されている時間がログインユーザーの打刻と一致している
     * 期待挙動：「休憩」の時間がログインユーザーの打刻と一致している
     */
    public function test_attendance_detail_shows_correct_rest_times(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);
        $attendance = $this->createAttendance($user, [
            'status'    => 3,
            'clock_in'  => Carbon::today()->setTime(9, 0),
            'clock_out' => Carbon::today()->setTime(18, 0),
        ]);
        RestTime::create([
            'attendance_id' => $attendance->id,
            'rest_start'    => Carbon::today()->setTime(12, 0),
            'rest_end'      => Carbon::today()->setTime(13, 0),
        ]);

        $response = $this->get("/attendance/detail/{$attendance->id}");

        $response->assertStatus(200);
        $response->assertSee('12:00');
        $response->assertSee('13:00');
    }

    // =========================================================
    // ID:11 勤怠詳細情報修正機能（一般ユーザー）
    // =========================================================

    /**
     * @test
     * 出勤時間が退勤時間より後になっている場合、エラーメッセージが表示される
     * 期待挙動：「出勤時間もしくは退勤時間が不適切な値です」が表示される
     */
    public function test_correction_request_shows_error_when_clock_in_is_after_clock_out(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);
        $attendance = $this->createAttendance($user, [
            'status'    => 3,
            'clock_in'  => Carbon::today()->setTime(9, 0),
            'clock_out' => Carbon::today()->setTime(18, 0),
        ]);

        $response = $this->post("/attendance/detail/{$attendance->id}", [
            'clock_in'  => '18:00',
            'clock_out' => '09:00',
            'remarks'   => '時刻逆転テスト',
        ]);

        $response->assertSessionHasErrors(['clock_in']);
        $errors = session('errors');
        $this->assertStringContainsString(
            '出勤時間もしくは退勤時間が不適切な値です',
            $errors->first('clock_in')
        );
    }

    /**
     * @test
     * 休憩開始時間が退勤時間より後になっている場合、エラーメッセージが表示される
     * 期待挙動：「休憩時間が不適切な値です」が表示される
     */
    public function test_correction_request_shows_error_when_rest_start_is_after_clock_out(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);
        $attendance = $this->createAttendance($user, [
            'status'    => 3,
            'clock_in'  => Carbon::today()->setTime(9, 0),
            'clock_out' => Carbon::today()->setTime(18, 0),
        ]);

        $response = $this->post("/attendance/detail/{$attendance->id}", [
            'clock_in'   => '09:00',
            'clock_out'  => '18:00',
            'rest_times' => [
                ['rest_start' => '19:00', 'rest_end' => '20:00'],
            ],
            'remarks'    => '休憩開始が退勤後テスト',
        ]);

        $response->assertSessionHasErrors();
        $errors = session('errors');
        $this->assertStringContainsString(
            '休憩時間が不適切な値です',
            $errors->first('rest_times.0.rest_start')
        );
    }

    /**
     * @test
     * 休憩終了時間が退勤時間より後になっている場合、エラーメッセージが表示される
     * 期待挙動：「休憩時間もしくは退勤時間が不適切な値です」が表示される
     */
    public function test_correction_request_shows_error_when_rest_end_is_after_clock_out(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);
        $attendance = $this->createAttendance($user, [
            'status'    => 3,
            'clock_in'  => Carbon::today()->setTime(9, 0),
            'clock_out' => Carbon::today()->setTime(18, 0),
        ]);

        $response = $this->post("/attendance/detail/{$attendance->id}", [
            'clock_in'   => '09:00',
            'clock_out'  => '18:00',
            'rest_times' => [
                ['rest_start' => '17:00', 'rest_end' => '19:00'],
            ],
            'remarks'    => '休憩終了が退勤後テスト',
        ]);

        $response->assertSessionHasErrors();
        $errors = session('errors');
        $this->assertStringContainsString(
            '休憩時間もしくは退勤時間が不適切な値です',
            $errors->first('rest_times.0.rest_end')
        );
    }

    /**
     * @test
     * 備考欄が未入力の場合のエラーメッセージが表示される
     * 期待挙動：「備考を記入してください」が表示される
     */
    public function test_correction_request_shows_error_when_remarks_is_empty(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);
        $attendance = $this->createAttendance($user, [
            'status'    => 3,
            'clock_in'  => Carbon::today()->setTime(9, 0),
            'clock_out' => Carbon::today()->setTime(18, 0),
        ]);

        $response = $this->post("/attendance/detail/{$attendance->id}", [
            'clock_in'  => '09:00',
            'clock_out' => '18:00',
            'remarks'   => '',
        ]);

        $response->assertSessionHasErrors([
            'remarks' => '備考を記入してください',
        ]);
    }

    /**
     * @test
     * 修正申請処理が実行される
     * 期待挙動：修正申請が実行され、管理者の承認画面と申請一覧画面に表示される
     */
    public function test_correction_request_is_saved_and_appears_in_request_list(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);
        $attendance = $this->createAttendance($user, [
            'status'    => 3,
            'clock_in'  => Carbon::today()->setTime(9, 0),
            'clock_out' => Carbon::today()->setTime(18, 0),
        ]);

        $this->post("/attendance/detail/{$attendance->id}", [
            'clock_in'  => '10:00',
            'clock_out' => '19:00',
            'remarks'   => '修正申請テスト',
        ]);

        // stamp_correction_requests に保存される
        $this->assertDatabaseHas('stamp_correction_requests', [
            'attendance_id' => $attendance->id,
            'user_id'       => $user->id,
            'status'        => 0,
        ]);

        // 申請一覧（承認待ち）に表示される
        $response = $this->get('/stamp_correction_request/list');
        $response->assertStatus(200);
        $response->assertSee('修正申請テスト');
    }

    /**
     * @test
     * 「承認待ち」にログインユーザーが行った申請が全て表示されていること
     * 期待挙動：申請一覧に自分の申請が全て表示されている
     */
    public function test_pending_requests_all_shown_in_request_list(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);
        $attendance = $this->createAttendance($user, ['status' => 3]);

        StampCorrectionRequest::create([
            'attendance_id' => $attendance->id,
            'user_id'       => $user->id,
            'remarks'       => '申請その1',
            'status'        => 0,
        ]);

        $response = $this->get('/stamp_correction_request/list');

        $response->assertStatus(200);
        $response->assertSee('申請その1');
    }

    /**
     * @test
     * 「承認済み」に管理者が承認した修正申請が全て表示されている
     * 期待挙動：承認済みに管理者が承認した申請が全て表示されている
     */
    public function test_approved_requests_all_shown_in_request_list(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);
        $attendance = $this->createAttendance($user, ['status' => 3]);

        StampCorrectionRequest::create([
            'attendance_id' => $attendance->id,
            'user_id'       => $user->id,
            'remarks'       => '承認済み申請',
            'status'        => 1,
            'approved_at'   => now(),
        ]);

        $response = $this->get('/stamp_correction_request/list');

        $response->assertStatus(200);
        $response->assertSee('承認済み申請');
    }

    /**
     * @test
     * 各申請の「詳細」を押下すると勤怠詳細画面に遷移する
     * 期待挙動：勤怠詳細画面に遷移する
     */
    public function test_request_list_detail_link_navigates_to_attendance_detail(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);
        $attendance = $this->createAttendance($user, [
            'status'    => 3,
            'clock_in'  => Carbon::today()->setTime(9, 0),
            'clock_out' => Carbon::today()->setTime(18, 0),
        ]);
        StampCorrectionRequest::create([
            'attendance_id' => $attendance->id,
            'user_id'       => $user->id,
            'remarks'       => 'テスト申請',
            'status'        => 0,
        ]);

        // 申請詳細 = 勤怠詳細画面に遷移できる
        $response = $this->get("/attendance/detail/{$attendance->id}");

        $response->assertStatus(200);
    }

    // =========================================================
    // ID:12 勤怠一覧情報取得機能（管理者）
    // =========================================================

    /**
     * @test
     * その日になされた全ユーザーの勤怠情報が正確に確認できる
     * 期待挙動：その日の全ユーザーの勤怠情報が正確な値になっている
     */
    public function test_admin_attendance_list_shows_all_users_today_records(): void
    {
        $admin = $this->createAdmin();
        $user  = $this->createUser(['name' => '鈴木 花子']);
        $this->actingAs($admin, 'admin');
        $this->createAttendance($user, [
            'status'    => 3,
            'clock_in'  => Carbon::today()->setTime(9, 0),
            'clock_out' => Carbon::today()->setTime(18, 0),
        ]);

        $response = $this->get('/admin/attendance/list');

        $response->assertStatus(200);
        $response->assertSee('鈴木 花子');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }

    /**
     * @test
     * 遷移した際に現在の日付が表示される
     * 期待挙動：勤怠一覧画面にその日の日付が表示されている
     */
    public function test_admin_attendance_list_shows_today_date_on_load(): void
    {
        $admin = $this->createAdmin();
        $this->actingAs($admin, 'admin');

        $response = $this->get('/admin/attendance/list');

        $response->assertStatus(200);
        $response->assertSee(Carbon::today()->format('Y-m-d'));
    }

    /**
     * @test
     * 「前日」を押下した時に前の日の勤怠情報が表示される
     * 期待挙動：前日の日付の勤怠情報が表示される
     */
    public function test_admin_attendance_list_shows_previous_day_when_param_given(): void
    {
        $admin     = $this->createAdmin();
        $this->actingAs($admin, 'admin');
        $yesterday = Carbon::yesterday()->format('Y-m-d');

        $response = $this->get('/admin/attendance/list?date=' . $yesterday);

        $response->assertStatus(200);
        $response->assertSee($yesterday);
    }

    /**
     * @test
     * 「翌日」を押下した時に次の日の勤怠情報が表示される
     * 期待挙動：翌日の日付の勤怠情報が表示される
     */
    public function test_admin_attendance_list_shows_next_day_when_param_given(): void
    {
        $admin    = $this->createAdmin();
        $this->actingAs($admin, 'admin');
        $tomorrow = Carbon::tomorrow()->format('Y-m-d');

        $response = $this->get('/admin/attendance/list?date=' . $tomorrow);

        $response->assertStatus(200);
        $response->assertSee($tomorrow);
    }

    // =========================================================
    // ID:13 勤怠詳細情報取得・修正機能（管理者）
    // =========================================================

    /**
     * @test
     * 勤怠詳細画面に表示されるデータが選択したものになっている
     * 期待挙動：詳細画面の内容が選択した情報と一致する
     */
    public function test_admin_attendance_detail_shows_selected_attendance_data(): void
    {
        $admin      = $this->createAdmin();
        $user       = $this->createUser();
        $this->actingAs($admin, 'admin');
        $attendance = $this->createAttendance($user, [
            'status'    => 3,
            'clock_in'  => Carbon::today()->setTime(9, 0),
            'clock_out' => Carbon::today()->setTime(18, 0),
        ]);

        $response = $this->get("/admin/attendance/{$attendance->id}");

        $response->assertStatus(200);
        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }

    /**
     * @test
     * 出勤時間が退勤時間より後になっている場合、エラーメッセージが表示される（管理者）
     * 期待挙動：「出勤時間もしくは退勤時間が不適切な値です」が表示される
     */
    public function test_admin_correction_shows_error_when_clock_in_is_after_clock_out(): void
    {
        $admin      = $this->createAdmin();
        $user       = $this->createUser();
        $this->actingAs($admin, 'admin');
        $attendance = $this->createAttendance($user, [
            'status'    => 3,
            'clock_in'  => Carbon::today()->setTime(9, 0),
            'clock_out' => Carbon::today()->setTime(18, 0),
        ]);

        $response = $this->post("/admin/attendance/{$attendance->id}", [
            'clock_in'  => '18:00',
            'clock_out' => '09:00',
            'remarks'   => '時刻逆転テスト（管理者）',
        ]);

        $response->assertSessionHasErrors();
        $errors = session('errors');
        $this->assertStringContainsString(
            '出勤時間もしくは退勤時間が不適切な値です',
            $errors->first('clock_in')
        );
    }

    /**
     * @test
     * 休憩開始時間が退勤時間より後になっている場合、エラーメッセージが表示される（管理者）
     * 期待挙動：「休憩時間が不適切な値です」が表示される
     */
    public function test_admin_correction_shows_error_when_rest_start_is_after_clock_out(): void
    {
        $admin      = $this->createAdmin();
        $user       = $this->createUser();
        $this->actingAs($admin, 'admin');
        $attendance = $this->createAttendance($user, [
            'status'    => 3,
            'clock_in'  => Carbon::today()->setTime(9, 0),
            'clock_out' => Carbon::today()->setTime(18, 0),
        ]);

        $response = $this->post("/admin/attendance/{$attendance->id}", [
            'clock_in'   => '09:00',
            'clock_out'  => '18:00',
            'rest_times' => [
                ['rest_start' => '19:00', 'rest_end' => '20:00'],
            ],
            'remarks'    => '休憩開始が退勤後（管理者）',
        ]);

        $response->assertSessionHasErrors();
        $errors = session('errors');
        $this->assertStringContainsString(
            '休憩時間が不適切な値です',
            $errors->first('rest_times.0.rest_start')
        );
    }

    /**
     * @test
     * 休憩終了時間が退勤時間より後になっている場合、エラーメッセージが表示される（管理者）
     * 期待挙動：「休憩時間もしくは退勤時間が不適切な値です」が表示される
     */
    public function test_admin_correction_shows_error_when_rest_end_is_after_clock_out(): void
    {
        $admin      = $this->createAdmin();
        $user       = $this->createUser();
        $this->actingAs($admin, 'admin');
        $attendance = $this->createAttendance($user, [
            'status'    => 3,
            'clock_in'  => Carbon::today()->setTime(9, 0),
            'clock_out' => Carbon::today()->setTime(18, 0),
        ]);

        $response = $this->post("/admin/attendance/{$attendance->id}", [
            'clock_in'   => '09:00',
            'clock_out'  => '18:00',
            'rest_times' => [
                ['rest_start' => '17:00', 'rest_end' => '19:00'],
            ],
            'remarks'    => '休憩終了が退勤後（管理者）',
        ]);

        $response->assertSessionHasErrors();
        $errors = session('errors');
        $this->assertStringContainsString(
            '休憩時間もしくは退勤時間が不適切な値です',
            $errors->first('rest_times.0.rest_end')
        );
    }

    /**
     * @test
     * 備考欄が未入力の場合のエラーメッセージが表示される（管理者）
     * 期待挙動：「備考を記入してください」が表示される
     */
    public function test_admin_correction_shows_error_when_remarks_is_empty(): void
    {
        $admin      = $this->createAdmin();
        $user       = $this->createUser();
        $this->actingAs($admin, 'admin');
        $attendance = $this->createAttendance($user, [
            'status'    => 3,
            'clock_in'  => Carbon::today()->setTime(9, 0),
            'clock_out' => Carbon::today()->setTime(18, 0),
        ]);

        $response = $this->post("/admin/attendance/{$attendance->id}", [
            'clock_in'  => '09:00',
            'clock_out' => '18:00',
            'remarks'   => '',
        ]);

        $response->assertSessionHasErrors([
            'remarks' => '備考を記入してください',
        ]);
    }

    // =========================================================
    // ID:14 ユーザー情報取得機能（管理者）
    // =========================================================

    /**
     * @test
     * 管理者ユーザーが全一般ユーザーの「氏名」「メールアドレス」を確認できる
     * 期待挙動：全ての一般ユーザーの氏名とメールアドレスが正しく表示されている
     */
    public function test_admin_staff_list_shows_all_users_name_and_email(): void
    {
        $admin = $this->createAdmin();
        $email = uniqid('tanaka_') . '@example.com';
        $user  = $this->createUser([
            'name'  => '田中 一郎',
            'email' => $email,
        ]);
        $this->actingAs($admin, 'admin');

        $response = $this->get('/admin/staff/list');

        $response->assertStatus(200);
        $response->assertSee('田中 一郎');
        $response->assertSee($email);
    }

    /**
     * @test
     * ユーザーの勤怠情報が正しく表示される
     * 期待挙動：勤怠情報が正確に表示される
     */
    public function test_admin_staff_attendance_shows_correct_data(): void
    {
        $admin = $this->createAdmin();
        $user  = $this->createUser();
        $this->actingAs($admin, 'admin');
        $this->createAttendance($user, [
            'status'    => 3,
            'clock_in'  => Carbon::today()->setTime(9, 0),
            'clock_out' => Carbon::today()->setTime(18, 0),
        ]);

        $response = $this->get("/admin/attendance/staff/{$user->id}");

        $response->assertStatus(200);
        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }

    /**
     * @test
     * 「前月」を押下した時に表示月の前月の情報が表示される（管理者スタッフ別）
     * 期待挙動：前月の情報が表示されている
     */
    public function test_admin_staff_attendance_shows_previous_month(): void
    {
        $admin     = $this->createAdmin();
        $user      = $this->createUser();
        $this->actingAs($admin, 'admin');
        $prevMonth = Carbon::now()->subMonth()->format('Y-m');

        $response = $this->get("/admin/attendance/staff/{$user->id}?month={$prevMonth}");

        $response->assertStatus(200);
        $response->assertSee(Carbon::now()->subMonth()->format('Y/m'));
    }

    /**
     * @test
     * 「翌月」を押下した時に表示月の翌月の情報が表示される（管理者スタッフ別）
     * 期待挙動：翌月の情報が表示されている
     */
    public function test_admin_staff_attendance_shows_next_month(): void
    {
        $admin     = $this->createAdmin();
        $user      = $this->createUser();
        $this->actingAs($admin, 'admin');
        $nextMonth = Carbon::now()->addMonth()->format('Y-m');

        $response = $this->get("/admin/attendance/staff/{$user->id}?month={$nextMonth}");

        $response->assertStatus(200);
        $response->assertSee(Carbon::now()->addMonth()->format('Y/m'));
    }

    /**
     * @test
     * 「詳細」を押下すると、その日の勤怠詳細画面に遷移する（管理者スタッフ別）
     * 期待挙動：その日の勤怠詳細画面に遷移する
     */
    public function test_admin_staff_attendance_detail_link_navigates_to_detail(): void
    {
        $admin      = $this->createAdmin();
        $user       = $this->createUser();
        $this->actingAs($admin, 'admin');
        $attendance = $this->createAttendance($user, [
            'status'    => 3,
            'clock_in'  => Carbon::today()->setTime(9, 0),
            'clock_out' => Carbon::today()->setTime(18, 0),
        ]);

        $response = $this->get("/admin/attendance/{$attendance->id}");

        $response->assertStatus(200);
    }

    // =========================================================
    // ID:15 勤怠情報修正機能（管理者）
    // =========================================================

    /**
     * @test
     * 承認待ちの修正申請が全て表示されている
     * 期待挙動：全ユーザーの未承認の修正申請が表示される
     */
    public function test_admin_request_list_shows_all_pending_requests(): void
    {
        $admin      = $this->createAdmin();
        $user       = $this->createUser();
        $this->actingAs($admin, 'admin');
        $attendance = $this->createAttendance($user, ['status' => 3]);
        StampCorrectionRequest::create([
            'attendance_id' => $attendance->id,
            'user_id'       => $user->id,
            'remarks'       => '承認待ち申請テスト',
            'status'        => 0,
        ]);

        $response = $this->get('/stamp_correction_request/list');

        $response->assertStatus(200);
        $response->assertSee('承認待ち申請テスト');
    }

    /**
     * @test
     * 承認済みの修正申請が全て表示されている
     * 期待挙動：全ユーザーの承認済みの修正申請が表示される
     */
    public function test_admin_request_list_shows_all_approved_requests(): void
    {
        $admin      = $this->createAdmin();
        $user       = $this->createUser();
        $this->actingAs($admin, 'admin');
        $attendance = $this->createAttendance($user, ['status' => 3]);
        StampCorrectionRequest::create([
            'attendance_id' => $attendance->id,
            'user_id'       => $user->id,
            'remarks'       => '承認済み申請テスト',
            'status'        => 1,
            'approved_at'   => now(),
        ]);

        $response = $this->get('/stamp_correction_request/list');

        $response->assertStatus(200);
        $response->assertSee('承認済み申請テスト');
    }

    /**
     * @test
     * 修正申請の詳細内容が正しく表示されている
     * 期待挙動：申請内容が正しく表示されている
     */
    public function test_admin_correction_request_detail_shows_correct_content(): void
    {
        $admin      = $this->createAdmin();
        $user       = $this->createUser();
        $this->actingAs($admin, 'admin');
        $attendance = $this->createAttendance($user, [
            'status'    => 3,
            'clock_in'  => Carbon::today()->setTime(9, 0),
            'clock_out' => Carbon::today()->setTime(18, 0),
        ]);
        $request = StampCorrectionRequest::create([
            'attendance_id' => $attendance->id,
            'user_id'       => $user->id,
            'new_clock_in'  => Carbon::today()->setTime(10, 0),
            'new_clock_out' => Carbon::today()->setTime(19, 0),
            'remarks'       => '詳細確認テスト',
            'status'        => 0,
        ]);

        $response = $this->get(
            "/admin/stamp_correction_request/approve/{$request->id}"
        );

        $response->assertStatus(200);
        $response->assertSee('10:00');
        $response->assertSee('19:00');
    }

    /**
     * @test
     * 修正申請の承認処理が正しく行われる
     * 期待挙動：修正申請が承認され、勤怠情報が更新される
     */
    public function test_admin_approving_request_updates_attendance_and_status(): void
    {
        $admin      = $this->createAdmin();
        $user       = $this->createUser();
        $this->actingAs($admin, 'admin');
        $attendance = $this->createAttendance($user, [
            'status'    => 3,
            'clock_in'  => Carbon::today()->setTime(9, 0),
            'clock_out' => Carbon::today()->setTime(18, 0),
        ]);
        $request = StampCorrectionRequest::create([
            'attendance_id' => $attendance->id,
            'user_id'       => $user->id,
            'new_clock_in'  => Carbon::today()->setTime(10, 0),
            'new_clock_out' => Carbon::today()->setTime(19, 0),
            'remarks'       => '承認処理テスト',
            'status'        => 0,
        ]);

        $this->post(
            "/admin/stamp_correction_request/approve/{$request->id}"
        );

        // 申請ステータスが承認済み（1）に更新される
        $this->assertDatabaseHas('stamp_correction_requests', [
            'id'     => $request->id,
            'status' => 1,
        ]);

        // 勤怠情報（出退勤時刻）が申請内容で更新される
        $attendance->refresh();
        $this->assertEquals(
            '10:00',
            Carbon::parse($attendance->clock_in)->format('H:i')
        );
        $this->assertEquals(
            '19:00',
            Carbon::parse($attendance->clock_out)->format('H:i')
        );
    }
}