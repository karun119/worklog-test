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

    /** 現在の日時情報がUIと同じ形式で出力されている */
    public function test_it_displays_current_date_and_time_on_attendance_page()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create();
        $testNow = Carbon::create(2025, 6, 1, 8, 0);
        Carbon::setTestNow($testNow);
        $weekdays = ['日', '月', '火', '水', '木', '金', '土'];
        $weekdayKanji = $weekdays[$testNow->dayOfWeek];
        $expectedDate = $testNow->year . '年' . $testNow->month . '月' . $testNow->day . '日(' . $weekdayKanji . ')';
        $expectedTime = $testNow->format('H:i');

        $response = $this->actingAs($user)->get('/attendance');
        $response->assertStatus(200);
        $response->assertSee($expectedDate);
        $response->assertSee($expectedTime);
    }


    /** 勤務外の場合、勤怠ステータスが正しく表示される */
    public function test_status_before_work_is_displayed()
    {
        $user = User::factory()->general()->beforeWork()->create();

        $this->actingAs($user)
            ->get('/attendance')
            ->assertStatus(200)
            ->assertSee('勤務外');
    }


    /** 出勤中の場合、勤怠ステータスが正しく表示される */
    public function test_status_working_is_displayed()
    {
        $user = User::factory()->general()->working()->create();
        Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => now()->toDateString(),
            'clock_in' => now()->subHours(1),
            'clock_out' => null,
        ]);

        $this->actingAs($user)
            ->get('/attendance')
            ->assertStatus(200)
            ->assertSee('出勤中');
    }


    /** 休憩中の場合、勤怠ステータスが正しく表示される */
    public function test_status_on_break_is_displayed()
    {
        $user = User::factory()->general()->onBreak()->create();
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => now()->toDateString(),
            'clock_in' => now()->subHours(2),
            'clock_out' => null,
        ]);
        BreakTime::factory()->create([
            'attendance_id' => $attendance->id,
            'break_in' => now()->subHour(),
            'break_out' => null,
        ]);

        $this->actingAs($user)
            ->get('/attendance')
            ->assertStatus(200)
            ->assertSee('休憩中');
    }


    /** 退勤済の場合、勤怠ステータスが正しく表示される */
    public function test_status_after_work_is_displayed()
    {
        $user = User::factory()->general()->afterWork()->create();
        Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => now()->toDateString(),
            'clock_in' => now()->subHours(9),
            'clock_out' => now()->subHour(),
        ]);

        $this->actingAs($user)
            ->get('/attendance')
            ->assertStatus(200)
            ->assertSee('退勤済');
    }


    /** 出勤ボタンが正しく機能する */
    public function test_clock_in_button_registers_working_status()
    {
        $user = User::factory()->general()->beforeWork()->create();
        $response = $this->actingAs($user)->get('/attendance');
        $response->assertStatus(200);
        $response->assertSee('出勤');
        $response = $this->actingAs($user)->post('/attendance', [
            'action' => 'clock_in',
        ]);
        $response->assertRedirect(route('attendance.index'));

        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'work_date' => Carbon::today()->toDateString(),
        ]);
        $attendance = Attendance::where('user_id', $user->id)
            ->where('work_date', Carbon::today()->toDateString())
            ->first();
        $this->assertNotNull($attendance->clock_in);
        $user->refresh();

        $this->assertEquals('working', $user->attendance_status);
    }


    /** 出勤は一日一回のみできる */
    public function test_clock_in_can_only_be_done_once_per_day()
    {
        $user = User::factory()->general()->afterWork()->create();
        Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => Carbon::today()->toDateString(),
            'clock_in' => now()->subHours(8),
            'clock_out' => now()->subHours(1),
        ]);

        $response = $this->actingAs($user)->get('/attendance');
        $response->assertStatus(200);
        $response->assertDontSee('<button type="button" class="btn btn-clock-in">', false);
        $response->assertDontSee('<button type="button" class="btn btn-clock-out">', false);
        $response->assertDontSee('<button type="button" class="btn btn-break-in">', false);
        $response->assertDontSee('<button type="button" class="btn btn-break-out">', false);
        $response->assertSee('お疲れ様でした');
    }


    /** 出勤時刻が勤怠一覧画面で確認できる */
    public function test_clock_in_time_is_displayed_in_attendance_list()
    {
        $user = User::factory()->general()->working()->create();
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => Carbon::today()->toDateString(),
            'clock_in' => now()->subHours(8)->format('H:i:s'),
        ]);
        $response = $this->actingAs($user)->get('/attendance/list');
        $response->assertStatus(200);
        $response->assertSee($attendance->clock_in->format('H:i'));
    }


    /** 休憩ボタンが正しく機能する */
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
        $breakIn = BreakTime::where('attendance_id', $attendance->id)
            ->latest()
            ->first()
            ->break_in;

        $this->assertEquals(now()->format('H:i'), Carbon::parse($breakIn)->format('H:i'));
    }


    /** 休憩は一日に何回でもできる */
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

        $this->actingAs($user)->post('/attendance', ['action' => 'break_in']);
        $user->refresh();

        $this->assertEquals('break', $user->attendance_status);
        $this->actingAs($user)->post('/attendance', ['action' => 'break_out']);
        $user->refresh();

        $this->assertEquals('working', $user->attendance_status);
        $response = $this->actingAs($user)->get('/attendance');
        $response->assertSee('休憩入');
    }


    /** 休憩戻ボタンが正しく機能する */
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
        $this->actingAs($user)->post('/attendance', ['action' => 'break_in']);

        $user->refresh();
        $this->assertEquals('break', $user->attendance_status);
        $this->actingAs($user)->post('/attendance', ['action' => 'break_out']);

        $user->refresh();
        $this->assertEquals('working', $user->attendance_status);
        $breakOut = BreakTime::where('attendance_id', $attendance->id)
            ->latest()
            ->first()
            ->break_out;

        $this->assertEquals(now()->format('H:i'), Carbon::parse($breakOut)->format('H:i'));
    }


    /** 休憩戻は一日に何回でもできる */
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
        $this->actingAs($user)->post('/attendance', ['action' => 'break_in']);
        $this->actingAs($user)->post('/attendance', ['action' => 'break_out']);
        $user->refresh();
        $this->assertEquals('working', $user->attendance_status);

        $this->actingAs($user)->post('/attendance', ['action' => 'break_in']);
        $this->actingAs($user)->post('/attendance', ['action' => 'break_out']);
        $user->refresh();
        $this->assertEquals('working', $user->attendance_status);
    }



    /** 休憩時刻が勤怠一覧画面で確認できる */
    public function test_break_total_is_displayed_in_attendance_list()
    {
        $user = User::factory()->general()->create();
        $baseTime = Carbon::today()->setTime(9, 0);
        $attendanceWithBreak = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => $baseTime->toDateString(),
            'clock_in' => $baseTime,
            'clock_out' => $baseTime->copy()->addHours(8),
        ]);
        BreakTime::factory()->create([
            'attendance_id' => $attendanceWithBreak->id,
            'break_in' => $baseTime->copy()->addHours(3),
            'break_out' => $baseTime->copy()->addHours(3)->addMinutes(30),
        ]);
        $attendanceWithBreak->refresh();

        $response = $this->actingAs($user)->get('/attendance/list');
        $response->assertStatus(200);
        $response->assertSee('0:30');

        $attendanceNoBreak = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => $baseTime->copy()->addDay()->toDateString(),
            'clock_in' => $baseTime,
            'clock_out' => $baseTime->copy()->addHours(8),
        ]);
        $attendanceNoBreak->refresh();
        $response = $this->actingAs($user)->get('/attendance/list');
        $response->assertStatus(200);
        $response->assertDontSee('00:');
    }


    /** 退勤ボタンが正しく機能する */
    public function test_clock_out_button_works_correctly()
    {
        $user = User::factory()->create([
            'attendance_status' => 'working',
        ]);
        Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => today(),
            'clock_in' => now()->subHours(2),
            'clock_out' => null,
        ]);
        /** @var \App\Models\User $user */
        $this->actingAs($user);
        $response = $this->post('/attendance?action=clock_out');
        $response->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'attendance_status' => 'after_work',
        ]);
        $attendance = Attendance::where('user_id', $user->id)
            ->where('work_date', today())
            ->first();

        $this->assertNotNull($attendance->clock_out);
    }


    /** 退勤時刻が勤怠一覧画面で確認できる */
    public function test_clock_out_time_is_displayed_in_attendance_list()
    {
        $user = User::factory()->beforeWork()->create();
        $this->actingAs($user);
        $this->assertDatabaseMissing('attendances', [
            'user_id' => $user->id,
            'work_date' => today(),
        ]);

        $this->post('/attendance?action=clock_in');
        $attendance = Attendance::where('user_id', $user->id)
            ->where('work_date', today())
            ->first();

        $this->assertNotNull($attendance->clock_in);
        $this->post('/attendance?action=clock_out');

        $attendance->refresh();
        $this->assertNotNull($attendance->clock_out);
        $response = $this->get('/attendance/list');
        $response->assertStatus(200);
        $formattedClockOut = $attendance->clock_out->format('H:i');
        $response->assertSee($formattedClockOut);
    }
}
