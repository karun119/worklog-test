<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\CorrectionRequest;
use App\Models\CorrectionBreakTime;



class AdminRequestControllerTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic feature test example.
     *
     * @return void
     */

    /** 承認待ちの修正申請が全て表示されている */
    public function test_pending_corrections_are_listed()
    {
        /** @var User $admin */
        $admin = User::factory()->create([
            'admin_status' => 'admin'
        ]);
        $this->actingAs($admin);
        $user = User::factory()->create([
            'admin_status' => 'general'
        ]);
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => '2025-11-30',
            'clock_in' => '09:45:00',
            'clock_out' => '17:57:00',
        ]);
        BreakTime::factory()->create([
            'attendance_id' => $attendance->id,
            'break_in' => '12:00:00',
            'break_out' => '12:45:00',
        ]);
        BreakTime::factory()->create([
            'attendance_id' => $attendance->id,
            'break_in' => '15:00:00',
            'break_out' => '15:15:00',
        ]);
        CorrectionRequest::factory()->create([
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'status' => 'pending',
            'created_by_admin' => false,
            'comment' => '修正申請サンプル'
        ]);

        $response = $this->get(route('admin.request.list'));
        $response->assertStatus(200);
        $response->assertSee('承認待ち');
        $response->assertSee('修正申請サンプル');
    }


    /** 承認済みの修正申請が全て表示されている */
    public function test_approved_corrections_are_listed()
    {
        /** @var User $admin */
        $admin = User::factory()->create([
            'admin_status' => 'admin'
        ]);
        $this->actingAs($admin);
        $user = User::factory()->create([
            'admin_status' => 'general'
        ]);
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => '2025-11-30',
            'clock_in' => '09:45:00',
            'clock_out' => '17:57:00',
        ]);
        BreakTime::factory()->create([
            'attendance_id' => $attendance->id,
            'break_in' => '12:00:00',
            'break_out' => '12:45:00',
        ]);
        BreakTime::factory()->create([
            'attendance_id' => $attendance->id,
            'break_in' => '15:00:00',
            'break_out' => '15:15:00',
        ]);
        CorrectionRequest::factory()->create([
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'status' => 'approved',
            'created_by_admin' => false,
            'comment' => '承認済み申請サンプル'
        ]);

        $response = $this->get(route('admin.request.list'));
        $response->assertStatus(200);
        $response->assertSee('承認済み');
        $response->assertSee('承認済み申請サンプル');
    }


    /** 修正申請の詳細内容が正しく表示されている */
    public function test_correction_details_are_displayed_correctly()
    {
        /** @var User $admin */
        $admin = User::factory()->create([
            'admin_status' => 'admin'
        ]);
        $this->actingAs($admin);
        $user = User::factory()->create([
            'admin_status' => 'general'
        ]);
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => '2025-11-30',
            'clock_in' => '09:45:00',
            'clock_out' => '17:57:00',
            'comment' => '元の勤怠コメント',
        ]);
        BreakTime::factory()->create([
            'attendance_id' => $attendance->id,
            'break_in' => '12:00:00',
            'break_out' => '12:45:00',
        ]);
        $correction = CorrectionRequest::factory()->create([
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'status' => 'pending',
            'created_by_admin' => false,
            'comment' => '修正申請コメント',
            'new_clock_in' => '09:50:00',
            'new_clock_out' => '18:00:00',
            'new_date' => '2025-11-30',
        ]);
        CorrectionBreakTime::factory()->create([
            'correction_request_id' => $correction->id,
            'new_break_in' => '12:00:00',
            'new_break_out' => '12:45:00',
        ]);

        $response = $this->get(route('admin.stamp.approve.show', $correction->id));
        $response->assertStatus(200);
        $response->assertSee($user->name);
        $response->assertSee('修正申請コメント');
        $response->assertSee('09:50');
        $response->assertSee('18:00');
        $response->assertSee('12:00');
        $response->assertSee('12:45');
    }


    /** 修正申請の承認処理が正しく行われる */
    public function test_correction_can_be_approved()
    {
        /** @var User $admin */
        $admin = User::factory()->create([
            'admin_status' => 'admin'
        ]);
        $this->actingAs($admin);
        $user = User::factory()->create([
            'admin_status' => 'general'
        ]);
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => '2025-11-30',
            'clock_in' => '09:45:00',
            'clock_out' => '17:57:00',
            'comment' => '元の勤怠コメント',
        ]);
        BreakTime::factory()->create([
            'attendance_id' => $attendance->id,
            'break_in' => '12:00:00',
            'break_out' => '12:45:00',
        ]);
        $correction = CorrectionRequest::factory()->create([
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'status' => 'pending',
            'created_by_admin' => false,
            'comment' => '修正申請コメント',
            'new_clock_in' => '09:50:00',
            'new_clock_out' => '18:00:00',
            'new_date' => '2025-11-30',
        ]);
        CorrectionBreakTime::factory()->create([
            'correction_request_id' => $correction->id,
            'new_break_in' => '12:00:00',
            'new_break_out' => '12:45:00',
        ]);

        $response = $this->post(route('admin.stamp.approve.update', $correction->id));
        $response->assertRedirect(route('admin.request.list'))
            ->assertSessionHas('success', '申請を承認しました');

        $this->assertDatabaseHas('correction_requests', [
            'id' => $correction->id,
            'status' => 'approved',
        ]);
        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'clock_in' => '09:45:00',
            'clock_out' => '17:57:00',
            'comment' => '元の勤怠コメント',
        ]);
        $this->assertDatabaseHas('break_times', [
            'attendance_id' => $attendance->id,
            'break_in' => '12:00:00',
            'break_out' => '12:45:00',
        ]);
    }
}
