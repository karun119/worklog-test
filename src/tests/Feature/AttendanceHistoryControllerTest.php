<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\Hash;


class AttendanceHistoryControllerTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic feature test example.
     *
     * @return void
     */

    /** 自分が行った勤怠情報が全て表示されている */
    public function test_attendance_list_displays_all_user_records()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create();
        $this->actingAs($user);
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => '2025-11-10',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);
        BreakTime::factory()->create([
            'attendance_id' => $attendance->id,
            'break_in' => '12:00:00',
            'break_out' => '12:30:00',
        ]);
        BreakTime::factory()->create([
            'attendance_id' => $attendance->id,
            'break_in' => '15:00:00',
            'break_out' => '15:15:00',
        ]);

        $response = $this->get('/attendance/list?month=2025-11');
        $response->assertSee('11/10(月)');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('0:45');
        $response->assertSee('8:15');
        $response->assertSee('11/11(火)');
        $response->assertSeeInOrder(['11/11(火)', '-']);
    }


    /** 勤怠一覧画面に遷移した際に現在の月が表示される */
    public function test_attendance_list_displays_current_month()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get('/attendance/list');
        $currentMonth = now()->format('Y/m');
        $response->assertSee($currentMonth);
    }


    /** 「前月」を押下した時に表示月の前月の情報が表示される */
    public function test_attendance_list_displays_previous_month_when_prev_clicked()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create();
        $this->actingAs($user);
        $prevMonth = now()->subMonth()->format('Y-m');
        $response = $this->get("/attendance/list?month={$prevMonth}");

        $displayMonth = now()->subMonth()->format('Y/m');
        $response->assertSee($displayMonth);
    }


    /** 「翌月」を押下した時に表示月の翌月の情報が表示される */
    public function test_attendance_list_displays_next_month_when_next_clicked()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create();
        $this->actingAs($user);
        $nextMonth = now()->addMonth()->format('Y-m');
        $response = $this->get("/attendance/list?month={$nextMonth}");
        $displayMonth = now()->addMonth()->format('Y/m');
        $response->assertSee($displayMonth);
    }


    /** 「詳細」を押下すると、その日の勤怠詳細画面に遷移する */
    public function test_attendance_detail_link_redirects_to_detail_page()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create();
        $this->actingAs($user);
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => '2025-11-10',
        ]);

        $response = $this->get("/attendance/list?month=2025-11");
        $response->assertSee(route('attendance.detail', ['id' => $attendance->id]));
        $detailResponse = $this->get(route('attendance.detail', ['id' => $attendance->id]));
        $detailResponse->assertStatus(200);
    }


    /** 勤怠詳細画面の「名前」がログインユーザーの氏名になっている */
    public function test_attendance_detail_displays_correct_user_name()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'name' => '山田太郎',
            'email' => 'taro@example.com',
            'password' => Hash::make('password'),
        ]);
        $this->actingAs($user);
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => '2025-11-10',
        ]);

        $response = $this->get(route('attendance.detail', ['id' => $attendance->id]));
        $response->assertStatus(200);
        $response->assertSee($user->name);
    }


    /** 勤怠詳細画面の「日付」が選択した日付になっている */
    public function test_attendance_detail_displays_correct_work_date()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'name' => '山田太郎',
            'email' => 'taro@example.com',
            'password' => Hash::make('password'),
        ]);
        $this->actingAs($user);
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => '2025-11-10',
        ]);

        $response = $this->get(route('attendance.detail', ['id' => $attendance->id]));
        $response->assertStatus(200);
        $response->assertSee('2025年');
        $response->assertSee('11月10日');
    }


    /** 「出勤・退勤」にて記されている時間がログインユーザーの打刻と一致している */
    public function test_attendance_detail_displays_correct_clock_in_and_clock_out()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'name' => '山田太郎',
            'email' => 'taro@example.com',
            'password' => Hash::make('password'),
        ]);
        $this->actingAs($user);
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => '2025-11-10',
            'clock_in' => '09:12:00',
            'clock_out' => '17:41:00',
        ]);

        $response = $this->get(route('attendance.detail', ['id' => $attendance->id]));
        $response->assertStatus(200);
        $response->assertSee('09:12');
        $response->assertSee('17:41');
    }


    /** 「休憩」にて記されている時間がログインユーザーの打刻と一致している */
    public function test_attendance_detail_displays_correct_break_times()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'name' => '山田太郎',
            'email' => 'taro@example.com',
            'password' => Hash::make('password'),
        ]);
        $this->actingAs($user);
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => '2025-11-10',
            'clock_in' => '09:12:00',
            'clock_out' => '17:41:00',
        ]);
        BreakTime::factory()->create([
            'attendance_id' => $attendance->id,
            'break_in' => '12:00:00',
            'break_out' => '12:45:00',
        ]);

        $response = $this->get(route('attendance.detail', ['id' => $attendance->id]));
        $response->assertStatus(200);
        $response->assertSee('12:00');
        $response->assertSee('12:45');
    }
}
