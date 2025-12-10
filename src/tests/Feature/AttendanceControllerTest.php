<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Carbon\Carbon;
use App\Models\Attendance;
use App\Models\BreakTime;


class AttendanceControllerTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    use RefreshDatabase;
    /** @test */

    // 現在の日時情報がUIと同じ形式で出力されている
    public function it_displays_current_date_and_time_on_attendance_page()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create();

        // テスト用に日時を固定（2025年6月1日 08:00）
        $testNow = Carbon::create(2025, 6, 1, 8, 0);
        Carbon::setTestNow($testNow);

        // UI形式で期待値を作成
        $weekdays = ['日', '月', '火', '水', '木', '金', '土'];
        $weekdayKanji = $weekdays[$testNow->dayOfWeek];
        $expectedDate = $testNow->year . '年' . $testNow->month . '月' . $testNow->day . '日(' . $weekdayKanji . ')';
        $expectedTime = $testNow->format('H:i');

        // 実行
        $response = $this->actingAs($user)->get('/attendance');

        $response->assertStatus(200);
        $response->assertSee($expectedDate);
        $response->assertSee($expectedTime);
    }
    // 勤務外の場合、勤怠ステータスが正しく表示される
    public function test_status_before_work_is_displayed()
    {
        $user = User::factory()->general()->beforeWork()->create();

        $this->actingAs($user)
            ->get('/attendance')
            ->assertStatus(200)
            ->assertSee('勤務外');
    }
    // 出勤中の場合、勤怠ステータスが正しく表示される
    public function test_status_working_is_displayed()
    {
        $user = User::factory()->general()->working()->create();

        // 当日の勤怠を作成
        Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => now()->toDateString(),
            'clock_in' => now()->subHours(1), // 出勤済
            'clock_out' => null,
        ]);

        $this->actingAs($user)
            ->get('/attendance')
            ->assertStatus(200)
            ->assertSee('出勤中');
    }
    // 休憩中の場合、勤怠ステータスが正しく表示される
    public function test_status_on_break_is_displayed()
    {
        $user = User::factory()->general()->onBreak()->create();

        // 当日の勤怠を作成（出勤済、休憩中）
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => now()->toDateString(),
            'clock_in' => now()->subHours(2),
            'clock_out' => null,
        ]);

        // 休憩中のデータを作成
        BreakTime::factory()->create([
            'attendance_id' => $attendance->id,
            'break_in' => now()->subHour(), // 休憩開始
            'break_out' => null,            // 休憩戻っていない
        ]);

        $this->actingAs($user)
            ->get('/attendance')
            ->assertStatus(200)
            ->assertSee('休憩中');
    }
    // 退勤済の場合、勤怠ステータスが正しく表示される
    public function test_status_after_work_is_displayed()
    {
        $user = User::factory()->general()->afterWork()->create();

        // 当日の勤怠を作成（出勤・退勤済）
        Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => now()->toDateString(),
            'clock_in' => now()->subHours(9),
            'clock_out' => now()->subHour(), // 退勤済
        ]);

        $this->actingAs($user)
            ->get('/attendance')
            ->assertStatus(200)
            ->assertSee('退勤済');
    }
    // 出勤ボタンが正しく機能する
    public function test_clock_in_button_registers_working_status()
    {
        // 1. ステータスが勤務外のユーザーを作成
        $user = User::factory()->general()->beforeWork()->create();

        // 2. ログイン状態で勤怠画面を表示、出勤ボタンがあることを確認
        $response = $this->actingAs($user)->get('/attendance');
        $response->assertStatus(200);
        $response->assertSee('出勤');

        // 3. 出勤処理をPOSTで実行
        $response = $this->actingAs($user)->post('/attendance', [
            'action' => 'clock_in',
        ]);

        // 4. リダイレクト先確認（勤怠画面）
        $response->assertRedirect(route('attendance.index'));

        // 5. データベースに勤怠記録が作成され、clock_inがセットされていることを確認
        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'work_date' => Carbon::today()->toDateString(),
        ]);

        $attendance = Attendance::where('user_id', $user->id)
            ->where('work_date', Carbon::today()->toDateString())
            ->first();

        $this->assertNotNull($attendance->clock_in);

        // 6. ユーザーのステータスが出勤中になっていることを確認
        $user->refresh();
        $this->assertEquals('working', $user->attendance_status);
    }
    // 出勤は一日一回のみできる
    public function test_clock_in_can_only_be_done_once_per_day()
    {
        // 1. ステータスが退勤済のユーザーを作成
        $user = User::factory()->general()->afterWork()->create();

        // 2. 当日の勤怠データを作成済み（出勤済・退勤済）
        Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => Carbon::today()->toDateString(),
            'clock_in' => now()->subHours(8),
            'clock_out' => now()->subHours(1),
        ]);

        // 3. ログイン状態で勤怠画面を表示
        $response = $this->actingAs($user)->get('/attendance');
        $response->assertStatus(200);

        // 4. 出勤ボタンが表示されないことを確認（CSSクラスで検索）
        $response->assertDontSee('<button type="button" class="btn btn-clock-in">', false);

        // 追加で他のボタンも表示されないことを確認するとより安全
        $response->assertDontSee('<button type="button" class="btn btn-clock-out">', false);
        $response->assertDontSee('<button type="button" class="btn btn-break-in">', false);
        $response->assertDontSee('<button type="button" class="btn btn-break-out">', false);

        // 5. 「お疲れ様でした」のメッセージが表示されていることを確認
        $response->assertSee('お疲れ様でした');
    }

    // 出勤時刻が勤怠一覧画面で確認できる
    public function test_clock_in_time_is_displayed_in_attendance_list()
    {
        // 1. 出勤済みユーザーを作成
        $user = User::factory()->general()->working()->create();

        // 2. 当日の勤怠データを作成
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => Carbon::today()->toDateString(),
            'clock_in' => now()->subHours(8)->format('H:i:s'),
        ]);

        // 3. 勤怠一覧画面を表示
        $response = $this->actingAs($user)->get('/attendance/list');
        $response->assertStatus(200);

        // 4. 出勤時刻が一覧画面に表示されていることを確認
        $response->assertSee($attendance->clock_in->format('H:i'));
    }

    public function test_break_in_button_registers_on_break_status()
    {
        $user = User::factory()->general()->create();

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => Carbon::today()->toDateString(),
            'clock_in' => now()->subHours(1),
            'clock_out' => null,
        ]);

        $user->refresh();
        $user->attendance_status = 'working';
        $user->save();

        $response = $this->actingAs($user)->get('/attendance');
        $response->assertStatus(200);
        $response->assertSee('休憩入');

        $response = $this->actingAs($user)->post('/attendance', [
            'action' => 'break_in',
        ]);

        $response->assertRedirect(route('attendance.index'));

        $user->refresh();
        $this->assertEquals('break', $user->attendance_status);

        // 秒は無視して分まで比較
        $breakIn = BreakTime::where('attendance_id', $attendance->id)
            ->latest()
            ->first()
            ->break_in;

        $this->assertEquals(now()->format('H:i'), Carbon::parse($breakIn)->format('H:i'));
    }

    // 休憩は一日に何回でもできる
    public function test_break_in_can_be_done_multiple_times_per_day()
    {
        $user = User::factory()->general()->create();

        Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => Carbon::today()->toDateString(),
            'clock_in' => now()->subHours(2),
            'clock_out' => null,
        ]);

        $user->attendance_status = 'working';
        $user->save();

        $response = $this->actingAs($user)->get('/attendance');
        $response->assertSee('休憩入');

        // 1回目の休憩
        $this->actingAs($user)->post('/attendance', ['action' => 'break_in']);
        $user->refresh();
        $this->assertEquals('break', $user->attendance_status);

        // 休憩戻して再度休憩入
        $this->actingAs($user)->post('/attendance', ['action' => 'break_out']);
        $user->refresh();
        $this->assertEquals('working', $user->attendance_status);

        $response = $this->actingAs($user)->get('/attendance');
        $response->assertSee('休憩入');
    }

    // 休憩戻ボタンが正しく機能する
    public function test_break_out_button_registers_working_status()
    {
        $user = User::factory()->general()->create();

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => Carbon::today()->toDateString(),
            'clock_in' => now()->subHours(1),
            'clock_out' => null,
        ]);

        $user->attendance_status = 'working';
        $user->save();

        // 休憩入
        $this->actingAs($user)->post('/attendance', ['action' => 'break_in']);
        $user->refresh();
        $this->assertEquals('break', $user->attendance_status);

        // 休憩戻
        $this->actingAs($user)->post('/attendance', ['action' => 'break_out']);
        $user->refresh();
        $this->assertEquals('working', $user->attendance_status);

        // データベースに休憩終了時刻が記録されていることを確認
        $breakOut = BreakTime::where('attendance_id', $attendance->id)
            ->latest()
            ->first()
            ->break_out;

        $this->assertEquals(now()->format('H:i'), Carbon::parse($breakOut)->format('H:i'));
    }
    // 休憩戻は一日に何回でもできる
    public function test_break_out_can_be_done_multiple_times_per_day()
    {
        $user = User::factory()->general()->create();

        Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => Carbon::today()->toDateString(),
            'clock_in' => now()->subHours(3),
            'clock_out' => null,
        ]);

        $user->attendance_status = 'working';
        $user->save();

        // 1回目の休憩入 → 休憩戻
        $this->actingAs($user)->post('/attendance', ['action' => 'break_in']);
        $this->actingAs($user)->post('/attendance', ['action' => 'break_out']);
        $user->refresh();
        $this->assertEquals('working', $user->attendance_status);

        // 2回目の休憩入 → 休憩戻
        $this->actingAs($user)->post('/attendance', ['action' => 'break_in']);
        $this->actingAs($user)->post('/attendance', ['action' => 'break_out']);
        $user->refresh();
        $this->assertEquals('working', $user->attendance_status);
    }

    // 休憩時刻が勤怠一覧画面で確認できる
    public function test_break_total_is_displayed_in_attendance_list()
    {
        $user = User::factory()->general()->create();

        // 基準日時を固定（Carbon::today() + 時刻固定）
        $baseTime = Carbon::today()->setTime(9, 0); // 例: 9:00 出勤

        // 勤怠作成（休憩あり）
        $attendanceWithBreak = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => $baseTime->toDateString(),
            'clock_in' => $baseTime,
            'clock_out' => $baseTime->copy()->addHours(8), // 17:00 退勤
        ]);

        // 休憩1回作成（12:00～12:30）
        BreakTime::factory()->create([
            'attendance_id' => $attendanceWithBreak->id,
            'break_in' => $baseTime->copy()->addHours(3), // 12:00
            'break_out' => $baseTime->copy()->addHours(3)->addMinutes(30), // 12:30
        ]);

        $attendanceWithBreak->refresh();


        // 勤怠一覧画面にアクセス
        $response = $this->actingAs($user)->get('/attendance/list');
        $response->assertStatus(200);

        // 合計休憩時間が表示されていることを確認
        $response->assertSee('0:30'); // "00:30" が期待値

        // =========================
        // 休憩なしの場合のテスト
        $attendanceNoBreak = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => $baseTime->copy()->addDay()->toDateString(),
            'clock_in' => $baseTime,
            'clock_out' => $baseTime->copy()->addHours(8),
        ]);

        $attendanceNoBreak->refresh();

        $response = $this->actingAs($user)->get('/attendance/list');
        $response->assertStatus(200);

        // 休憩なしの場合は空欄が表示されている
        $response->assertDontSee('00:'); // "00:00" が表示されないこと
    }

    // 退勤ボタンが正しく機能する
    public function test_clock_out_button_works_correctly()
    {
        $user = User::factory()->create([
            'attendance_status' => 'working',
        ]);

        // 今日の勤怠レコードを「勤務中」で作成
        Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => today(),
            'clock_in' => now()->subHours(2),
            'clock_out' => null,
        ]);

        /** @var \App\Models\User $user */
        $this->actingAs($user);

        // 退勤処理を実行（本来の正しいルート）
        $response = $this->post('/attendance?action=clock_out');
        $response->assertRedirect();

        // ユーザーのステータスが勤務後に更新される
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'attendance_status' => 'after_work',
        ]);

        // 勤怠データの退勤が記録されている
        $attendance = Attendance::where('user_id', $user->id)
            ->where('work_date', today())
            ->first();

        $this->assertNotNull($attendance->clock_out);
    }

    // 退勤時刻が勤怠一覧画面で確認できる
    public function test_clock_out_time_is_displayed_in_attendance_list()
    {
        // 勤務外のユーザーを作成
        $user = User::factory()->beforeWork()->create();

        $this->actingAs($user);

        // 今日の勤怠レコードがまだないことを確認
        $this->assertDatabaseMissing('attendances', [
            'user_id' => $user->id,
            'work_date' => today(),
        ]);

        // 出勤処理
        $this->post('/attendance?action=clock_in');

        // 勤怠レコード取得
        $attendance = Attendance::where('user_id', $user->id)
            ->where('work_date', today())
            ->first();

        $this->assertNotNull($attendance->clock_in);

        // 退勤処理
        $this->post('/attendance?action=clock_out');

        // DB確認：退勤時刻が記録されている
        $attendance->refresh();
        $this->assertNotNull($attendance->clock_out);

        // 勤怠一覧ページを表示
        $response = $this->get('/attendance/list');
        $response->assertStatus(200);

        // 今日の日付行に退勤時刻が表示されているか確認
        $formattedClockOut = $attendance->clock_out->format('H:i'); // HH:MM 表示
        $response->assertSee($formattedClockOut);
    }
}
