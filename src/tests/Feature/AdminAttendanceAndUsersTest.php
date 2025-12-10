<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class AdminAttendanceAndUsersTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    use RefreshDatabase;

    // その日になされた全ユーザーの勤怠情報が正確に確認できる
    public function test_admin_can_view_all_users_attendance_of_the_day()
    {
        $today = Carbon::today()->format('Y-m-d');
        $admin = User::factory()->admin()->create();
        $userA = User::factory()->general()->create(['name' => '一般太郎']);
        $userB = User::factory()->general()->create(['name' => '一般花子']);

        Attendance::factory()->create([
            'user_id' => $userA->id,
            'work_date' => $today,
            'clock_in' => '09:00',
            'clock_out' => '18:00',
        ]);
        Attendance::factory()->create([
            'user_id' => $userB->id,
            'work_date' => $today,
            'clock_in' => '10:00',
            'clock_out' => '19:00',
        ]);

        $this->actingAs($admin)
            ->get('/admin/attendance/list')
            ->assertStatus(200)
            ->assertSee('一般太郎')
            ->assertSee('一般花子')
            ->assertSee('09:00')
            ->assertSee('18:00')
            ->assertSee('10:00')
            ->assertSee('19:00');
    }

    //  遷移した際に現在の日付が表示される
    public function test_current_date_is_displayed_on_page_load()
    {
        $today = Carbon::today()->format('Y年n月j日');
        $admin = User::factory()->admin()->create();
        User::factory()->general()->create();

        $this->actingAs($admin)
            ->get('/admin/attendance/list')
            ->assertStatus(200)
            ->assertSee($today . 'の勤怠');
    }

    // 「前日」を押下した時に前の日の勤怠情報が表示される
    public function test_previous_day_button_shows_yesterdays_attendance()
    {
        $admin = User::factory()->admin()->create();
        $yesterday = Carbon::yesterday()->format('Y-m-d');
        $today     = Carbon::today()->format('Y-m-d');
        $user = User::factory()->general()->create(['name' => '前日のユーザー']);
        Attendance::factory()->create([
            'user_id'   => $user->id,
            'work_date' => $yesterday,
            'clock_in'  => '09:30',
            'clock_out' => '18:00',
        ]);
        Attendance::factory()->create([
            'user_id'   => $user->id,
            'work_date' => $today,
            'clock_in'  => '08:00',
            'clock_out' => '17:00',
        ]);

        $this->actingAs($admin)
            ->get('/admin/attendance/list?date=' . $yesterday)
            ->assertStatus(200)
            ->assertSee('前日のユーザー')
            ->assertSee('09:30')
            ->assertSee('18:00')
            ->assertDontSee('08:00')
            ->assertDontSee('17:00');
    }

    // 「翌日」を押下した時に次の日の勤怠情報が表示される
    public function test_next_day_button_shows_tomorrows_attendance()
    {
        $admin = User::factory()->admin()->create();
        $tomorrow = Carbon::tomorrow()->format('Y-m-d');
        $today    = Carbon::today()->format('Y-m-d');
        $user = User::factory()->general()->create(['name' => '翌日のユーザー']);
        Attendance::factory()->create([
            'user_id'   => $user->id,
            'work_date' => $tomorrow,
            'clock_in'  => '08:45',
            'clock_out' => '17:30',
        ]);
        Attendance::factory()->create([
            'user_id'   => $user->id,
            'work_date' => $today,
            'clock_in'  => '09:00',
            'clock_out' => '18:00',
        ]);

        $this->actingAs($admin)
            ->get('/admin/attendance/list?date=' . $tomorrow)
            ->assertStatus(200)
            ->assertSee('翌日のユーザー')
            ->assertSee('08:45')
            ->assertSee('17:30')
            ->assertDontSee('09:00')
            ->assertDontSee('18:00');
    }

    // 勤怠詳細画面に表示されるデータが選択したものになっている
    public function test_attendance_detail_displays_correct_data()
    {
        /** @var User $admin */
        $admin = User::factory()->create(['admin_status' => 'admin']);
        $this->actingAs($admin);
        $user = User::factory()->create(['admin_status' => 'general']);
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => today(),
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'comment' => '通常勤務',
        ]);
        BreakTime::factory()->create([
            'attendance_id' => $attendance->id,
            'break_in' => '12:00',
            'break_out' => '13:00',
        ]);

        $response = $this->get(route('admin.attendance.detail', ['id' => $attendance->id]));
        $response->assertStatus(200);
        $response->assertSee($user->name);
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('12:00');
        $response->assertSee('13:00');
        $response->assertSee('通常勤務');
    }

    // 出勤時間が退勤時間より後になっている場合、エラーメッセージが表示される
    public function test_error_displayed_when_clock_in_after_clock_out_admin()
    {
        /** @var User $admin */
        $admin = User::factory()->create(['admin_status' => 'admin']);
        $this->actingAs($admin);
        $user = User::factory()->create(['admin_status' => 'general']);
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => today(),
            'clock_in' => '09:00',
            'clock_out' => '17:00',
        ]);

        $response = $this->from(route('admin.attendance.detail', ['id' => $attendance->id]))
            ->post(route('admin.attendance.update', ['id' => $attendance->id]), [
                'clock_in' => '18:00',
                'clock_out' => '09:00',
                'break_in' => [],
                'break_out' => [],
                'comment' => 'Test comment',
                'work_date' => $attendance->work_date->format('Y-m-d'),
                'user_id' => $user->id,
            ]);
        $response->assertRedirect(route('admin.attendance.detail', ['id' => $attendance->id]));
        $response->assertSessionHasErrors([
            'clock_out' => '出勤時間もしくは退勤時間が不適切な値です',
        ]);
    }

    // 休憩開始時間が退勤時間より後になっている場合、エラーメッセージが表示される
    public function test_error_displayed_when_break_in_after_clock_out_admin()
    {
        /** @var User $admin */
        $admin = User::factory()->create(['admin_status' => 'admin']);
        $this->actingAs($admin);
        $user = User::factory()->create(['admin_status' => 'general']);
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => today(),
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'comment' => '通常勤務',
        ]);
        BreakTime::factory()->create([
            'attendance_id' => $attendance->id,
            'break_in' => '12:00',
            'break_out' => '13:00',
        ]);

        $response = $this->from(route('admin.attendance.detail', ['id' => $attendance->id]))
            ->post(route('admin.attendance.update', ['id' => $attendance->id]), [
                'clock_in' => '09:00',
                'clock_out' => '18:00',
                'break_in' => ['19:00'],
                'break_out' => ['19:30'],
                'comment' => 'Test comment',
                'work_date' => $attendance->work_date->format('Y-m-d'),
                'user_id' => $user->id,
            ]);
        $response->assertRedirect(route('admin.attendance.detail', ['id' => $attendance->id]));
        $response->assertSessionHasErrors([
            'break_in.0' => '休憩時間が不適切な値です',
        ]);
    }

    // 休憩終了時間が退勤時間より後になっている場合、エラーメッセージが表示される
    public function test_error_displayed_when_break_out_after_clock_out_admin()
    {
        /** @var User $admin */
        $admin = User::factory()->create(['admin_status' => 'admin']);
        $this->actingAs($admin);

        $user = User::factory()->create(['admin_status' => 'general']);
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => today(),
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'comment' => '通常勤務',
        ]);
        BreakTime::factory()->create([
            'attendance_id' => $attendance->id,
            'break_in' => '12:00',
            'break_out' => '13:00',
        ]);

        $response = $this->from(route('admin.attendance.detail', ['id' => $attendance->id]))
            ->post(route('admin.attendance.update', ['id' => $attendance->id]), [
                'clock_in' => '09:00',
                'clock_out' => '18:00',
                'break_in' => ['12:00'],
                'break_out' => ['19:00'],
                'comment' => 'Test comment',
                'work_date' => $attendance->work_date->format('Y-m-d'),
                'user_id' => $user->id,
            ]);
        $response->assertRedirect(route('admin.attendance.detail', ['id' => $attendance->id]));
        $response->assertSessionHasErrors([
            'break_out.0' => '休憩時間もしくは退勤時間が不適切な値です',
        ]);
    }

    // 備考欄が未入力の場合のエラーメッセージが表示される
    public function test_error_displayed_when_comment_is_empty_admin()
    {
        /** @var User $admin */
        $admin = User::factory()->create(['admin_status' => 'admin']);
        $this->actingAs($admin);
        $user = User::factory()->create(['admin_status' => 'general']);
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => today(),
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'comment' => '通常勤務',
        ]);
        BreakTime::factory()->create([
            'attendance_id' => $attendance->id,
            'break_in' => '12:00',
            'break_out' => '13:00',
        ]);

        $response = $this->from(route('admin.attendance.detail', ['id' => $attendance->id]))
            ->post(route('admin.attendance.update', ['id' => $attendance->id]), [
                'clock_in' => '09:00',
                'clock_out' => '18:00',
                'break_in' => ['12:00'],
                'break_out' => ['13:00'],
                'comment' => '',
                'work_date' => $attendance->work_date->format('Y-m-d'),
                'user_id' => $user->id,
            ]);
        $response->assertRedirect(route('admin.attendance.detail', ['id' => $attendance->id]));
        $response->assertSessionHasErrors([
            'comment' => '備考を記入してください',
        ]);
    }

    // 補足：
    // テストケースには書かれていませんが、
    // 出勤・退勤時間は必須バリデーションを追加しているため、未入力時にエラーが返るかテストで確認。
    public function test_clock_in_and_clock_out_are_required_admin()
    {
        /** @var User $admin */
        $admin = User::factory()->create(['admin_status' => 'admin']);
        $this->actingAs($admin);
        $user = User::factory()->create(['admin_status' => 'general']);
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => today(),
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'comment' => '通常勤務',
        ]);
        BreakTime::factory()->create([
            'attendance_id' => $attendance->id,
            'break_in' => '12:00',
            'break_out' => '12:30',
        ]);

        $response = $this->from(route('admin.attendance.detail', ['id' => $attendance->id]))
            ->post(route('admin.attendance.update', ['id' => $attendance->id]), [
                'clock_in' => '',
                'clock_out' => '18:00',
                'break_in' => ['12:00'],
                'break_out' => ['12:30'],
                'comment' => '修正テスト',
                'work_date' => $attendance->work_date->format('Y-m-d'),
                'user_id' => $user->id,
            ]);

        $response->assertRedirect(route('admin.attendance.detail', ['id' => $attendance->id]));
        $response->assertSessionHasErrors([
            'clock_in' => '出勤時間を入力してください',
        ]);

        $response = $this->from(route('admin.attendance.detail', ['id' => $attendance->id]))
            ->post(route('admin.attendance.update', ['id' => $attendance->id]), [
                'clock_in' => '09:00',
                'clock_out' => '',
                'break_in' => ['12:00'],
                'break_out' => ['12:30'],
                'comment' => '修正テスト',
                'work_date' => $attendance->work_date->format('Y-m-d'),
                'user_id' => $user->id,
            ]);
        $response->assertRedirect(route('admin.attendance.detail', ['id' => $attendance->id]));
        $response->assertSessionHasErrors([
            'clock_out' => '退勤時間を入力してください',
        ]);
    }

    // 管理者ユーザーが全一般ユーザーの「氏名」「メールアドレス」を確認できる
    public function test_admin_can_view_staff_list()
    {
        /** @var User $admin */
        $admin = User::factory()->create(['admin_status' => 'admin']);
        $staff1 = User::factory()->create(['admin_status' => 'general']);
        $staff2 = User::factory()->create(['admin_status' => 'general']);

        $this->actingAs($admin);
        $response = $this->get(route('admin.staff.list'));
        $response->assertStatus(200);
        $response->assertSee($staff1->name);
        $response->assertSee($staff1->email);
        $response->assertSee($staff2->name);
        $response->assertSee($staff2->email);
    }

    // ユーザーの勤怠情報が正しく表示される
    public function test_admin_can_view_staff_attendance_list()
    {
        /** @var User $admin */
        $admin = User::factory()->create(['admin_status' => 'admin']);
        $staff = User::factory()->create(['admin_status' => 'general']);
        $attendance = Attendance::factory()->create([
            'user_id' => $staff->id,
            'work_date' => now()->format('Y-m-d'),
            'clock_in' => '09:00',
            'clock_out' => '18:00',
        ]);
        BreakTime::factory()->create([
            'attendance_id' => $attendance->id,
            'break_in' => '12:00',
            'break_out' => '12:30',
        ]);
        BreakTime::factory()->create([
            'attendance_id' => $attendance->id,
            'break_in' => '15:00',
            'break_out' => '15:15',
        ]);

        $this->actingAs($admin);
        $response = $this->get('/admin/attendance/staff/' . $staff->id);
        $response->assertStatus(200);
        $response->assertSee($staff->name . 'さんの勤怠');
        $today = now();

        \Carbon\Carbon::setLocale('ja');
        $today = \Carbon\Carbon::today();
        $formattedDate = $today->isoFormat('MM/DD(ddd)');

        $response->assertSee($formattedDate);
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('0:45');
        $response->assertSee('8:15');
    }

    // 「前月」を押下した時に表示月の前月の情報が表示される
    public function test_admin_can_view_previous_month_attendance()
    {
        /** @var User $admin */
        $admin = User::factory()->create(['admin_status' => 'admin']);
        $staff = User::factory()->create(['admin_status' => 'general']);

        $attendance = Attendance::factory()->create([
            'user_id' => $staff->id,
            'work_date' => '2024-12-10',
            'clock_in' => '09:00',
            'clock_out' => '18:00',
        ]);
        BreakTime::factory()->create([
            'attendance_id' => $attendance->id,
            'break_in' => '12:00',
            'break_out' => '12:30',
        ]);
        Carbon::setLocale('ja');
        $formattedDate = Carbon::parse('2024-12-10')->isoFormat('MM/DD(ddd)');

        $this->actingAs($admin);
        $response = $this->get(route('admin.attendance.staff', [
            'id' => $staff->id,
            'month' => '2024-12'
        ]));
        $response->assertStatus(200);
        $response->assertSee($formattedDate);
        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }

    // 「翌月」を押下した時に表示月の翌月の情報が表示される
    public function test_admin_can_view_next_month_attendance()
    {
        /** @var User $admin */
        $admin = User::factory()->create(['admin_status' => 'admin']);
        $staff = User::factory()->create(['admin_status' => 'general']);
        $attendance = Attendance::factory()->create([
            'user_id' => $staff->id,
            'work_date' => '2025-02-05',
            'clock_in' => '10:00',
            'clock_out' => '19:00',
        ]);
        BreakTime::factory()->create([
            'attendance_id' => $attendance->id,
            'break_in' => '14:00',
            'break_out' => '14:45',
        ]);
        Carbon::setLocale('ja');
        $formattedDate = Carbon::parse('2025-02-05')->isoFormat('MM/DD(ddd)');

        $this->actingAs($admin);
        $response = $this->get(route('admin.attendance.staff', [
            'id' => $staff->id,
            'month' => '2025-02'
        ]));
        $response->assertStatus(200);
        $response->assertSee($formattedDate);
        $response->assertSee('10:00');
        $response->assertSee('19:00');
        $response->assertSee('0:45');
        $response->assertSee('8:15');
    }

    // 「詳細」を押下すると、その日の勤怠詳細画面に遷移する
    public function test_admin_can_jump_to_attendance_detail_page()
    {
        /** @var User $admin */
        $admin = User::factory()->create(['admin_status' => 'admin']);
        $staff = User::factory()->create(['admin_status' => 'general']);
        $attendance = Attendance::factory()->create([
            'user_id' => $staff->id,
            'work_date' => '2025-11-10',
            'clock_in' => '09:00',
            'clock_out' => '18:00',
        ]);

        $this->actingAs($admin);
        $response = $this->get(route('admin.attendance.staff', [
            'id' => $staff->id,
            'month' => '2025-11'
        ]));
        $detailUrl = route('admin.attendance.detail', [
            'id' => $attendance->id
        ]);
        $response->assertSee($detailUrl);
    }
}
