<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\CorrectionRequest;
use App\Models\CorrectionBreakTime;
use Illuminate\Foundation\Testing\RefreshDatabase;


class AttendanceCorrectionTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */

    use RefreshDatabase;

    /** 出勤時間が退勤時間より後になっている場合、エラーメッセージが表示される */
    public function test_error_displayed_when_clock_in_after_clock_out()
    {
        /** @var User $user */
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $this->actingAs($user);
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => today(), // Controller の条件に合わせる
            'clock_in' => '09:00',
            'clock_out' => '17:00',
        ]);

        $response = $this->from(route('attendance.detail', ['id' => $attendance->id]))
            ->post(route('attendance.update', ['id' => $attendance->id]), [
                'clock_in'  => '18:00',
                'clock_out' => '09:00',
                'break_in'  => [],
                'break_out' => [],
                'comment'   => 'Test comment',
                'work_date' => $attendance->work_date->format('Y-m-d'),
            ]);
        $response->assertRedirect(route('attendance.detail', ['id' => $attendance->id]));
        $response->assertSessionHasErrors([
            'clock_out' => '出勤時間もしくは退勤時間が不適切な値です',
        ]);
    }


    /** 休憩開始時間が退勤時間より後になっている場合、エラーメッセージが表示される */
    public function test_error_displayed_when_break_in_after_clock_out()
    {
        /** @var User $user */
        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->actingAs($user);
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
        $response = $this->from(route('attendance.detail', ['id' => $attendance->id]))
            ->post(route('attendance.update', ['id' => $attendance->id]), [
                'clock_in'  => '09:00',
                'clock_out' => '18:00',
                'break_in'  => ['19:00'],
                'break_out' => ['19:30'],
                'comment'   => 'Test comment',
                'work_date' => $attendance->work_date->format('Y-m-d'),
            ]);
        $response->assertRedirect(route('attendance.detail', ['id' => $attendance->id]));
        $response->assertSessionHasErrors(['break_in.0' => '休憩時間が不適切な値です']);
    }


    /** 休憩終了時間が退勤時間より後になっている場合、エラーメッセージが表示される */
    public function test_error_displayed_when_break_out_after_clock_out()
    {
        /** @var User $user */
        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->actingAs($user);
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
        $response = $this->from(route('attendance.detail', ['id' => $attendance->id]))
            ->post(route('attendance.update', ['id' => $attendance->id]), [
                'clock_in'  => '09:00',
                'clock_out' => '18:00',
                'break_in'  => ['12:00'],
                'break_out' => ['19:00'],
                'comment'   => 'Test comment',
                'work_date' => $attendance->work_date->format('Y-m-d'),
            ]);
        $response->assertRedirect(route('attendance.detail', ['id' => $attendance->id]));
        $response->assertSessionHasErrors(['break_out.0' => '休憩時間もしくは退勤時間が不適切な値です']);
    }


    /** 備考欄が未入力の場合のエラーメッセージが表示される */
    public function test_error_displayed_when_comment_is_empty()
    {
        /** @var User $user */
        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->actingAs($user);
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
        $response = $this->from(route('attendance.detail', ['id' => $attendance->id]))
            ->post(route('attendance.update', ['id' => $attendance->id]), [
                'clock_in'  => '09:00',
                'clock_out' => '18:00',
                'break_in'  => ['12:00'],
                'break_out' => ['13:00'],
                'comment'   => '',
                'work_date' => $attendance->work_date->format('Y-m-d'),
            ]);
        $response->assertRedirect(route('attendance.detail', ['id' => $attendance->id]));
        $response->assertSessionHasErrors(['comment' => '備考を記入してください']);
    }


    /** 修正申請処理が実行される */
    public function test_correction_request_is_created_with_break_times()
    {
        /** @var User $user */
        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->actingAs($user);
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
        $response = $this->post(route('attendance.update', ['id' => $attendance->id]), [
            'clock_in'  => '09:30',
            'clock_out' => '18:30',
            'break_in'  => ['12:30'],
            'break_out' => ['13:30'],
            'comment'   => '修正申請テスト',
            'work_date' => $attendance->work_date->format('Y-m-d'),
        ]);

        $response->assertRedirect(route('attendance.list', ['month' => $attendance->work_date->format('Y-m')]));
        $correctionRequest = CorrectionRequest::where('attendance_id', $attendance->id)
            ->where('user_id', $user->id)
            ->first();

        $this->assertNotNull($correctionRequest, 'CorrectionRequest が作成されていません');
        $this->assertEquals('修正申請テスト', $correctionRequest->comment);
        $this->assertEquals('pending', $correctionRequest->status);
        $this->assertEquals('09:30:00', $correctionRequest->new_clock_in->format('H:i:s'));
        $this->assertEquals('18:30:00', $correctionRequest->new_clock_out->format('H:i:s'));
        $this->assertEquals($attendance->work_date->format('Y-m-d'), $correctionRequest->new_date->format('Y-m-d'));

        $correctionBreak = CorrectionBreakTime::where('correction_request_id', $correctionRequest->id)->first();
        $this->assertNotNull($correctionBreak, 'CorrectionBreakTime が作成されていません');
        $this->assertEquals('12:30:00', $correctionBreak->new_break_in->format('H:i:s'));
        $this->assertEquals('13:30:00', $correctionBreak->new_break_out->format('H:i:s'));

        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $adminApprovalResponse = $this->get(route('admin.stamp.approve.show', ['attendance_correct_request_id' => $correctionRequest->id]));
        $adminApprovalResponse->assertStatus(200);
        $adminApprovalResponse->assertSeeText($user->name);
        $adminApprovalResponse->assertSeeText('09:30');
        $adminApprovalResponse->assertSeeText('18:30');
        $adminApprovalResponse->assertSeeText('12:30');
        $adminApprovalResponse->assertSeeText('13:30');
        $adminApprovalResponse->assertSeeText('修正申請テスト');
        $adminListResponse = $this->get(route('admin.request.list'));
        $adminListResponse->assertStatus(200);

        $viewData = $adminListResponse->original->getData();

        $this->assertTrue(
            $viewData['pendingRequests']->contains(fn($req) => $req->id === $correctionRequest->id),
            '承認待ちタブにCorrectionRequestが存在しません'
        );
    }


    /**
     * 補足：テストケースには書かれていませんが、出勤・退勤時間は必須バリデーションを追加しているため、未入力時にエラーが返るかテストで確認。
     */
    public function test_clock_in。and_clock_out_are_required()
    {
        /** @var User $user */
        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->actingAs($user);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => today(),
        ]);

        $response = $this->post(route('attendance.update', ['id' => $attendance->id]), [
            'clock_in'  => '',
            'clock_out' => '18:00',
            'comment'   => 'テスト',
            'break_in'  => [],
            'break_out' => [],
            'work_date' => today()->format('Y-m-d'),
        ]);
        $response->assertSessionHasErrors([
            'clock_in' => '出勤時間を入力してください',
        ]);

        $response = $this->post(route('attendance.update', ['id' => $attendance->id]), [
            'clock_in'  => '09:00',
            'clock_out' => '',
            'comment'   => 'テスト',
            'break_in'  => [],
            'break_out' => [],
            'work_date' => today()->format('Y-m-d'),
        ]);
        $response->assertSessionHasErrors([
            'clock_out' => '退勤時間を入力してください',
        ]);
    }


    /**
     * 補足：テストケースには書かれていませんが、休憩開始（break_in）と休憩終了（break_out）はペアで入力する必要があることをテスト。
     */
    public function test_break_in_and_break_out_must_be_pair_user()
    {
        /** @var User $user */
        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->actingAs($user);
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => today(),
            'clock_in' => '09:00',
            'clock_out' => '18:00',
        ]);

        $response = $this->from(route('attendance.detail', ['id' => $attendance->id]))
            ->post(route('attendance.update', ['id' => $attendance->id]), [
                'clock_in'  => '09:00',
                'clock_out' => '18:00',
                'break_in'  => ['12:00'],
                'break_out' => [''],
                'work_date' => $attendance->work_date->format('Y-m-d'),
                'comment'   => '修正テスト',
            ]);
        $response->assertRedirect(route('attendance.detail', ['id' => $attendance->id]));
        $response->assertSessionHasErrors([
            'break_in.0'  => '休憩開始と休憩終了はセットで入力してください',
        ]);

        $response = $this->from(route('attendance.detail', ['id' => $attendance->id]))
            ->post(route('attendance.update', ['id' => $attendance->id]), [
                'clock_in'  => '09:00',
                'clock_out' => '18:00',
                'break_in'  => [''],
                'break_out' => ['12:30'],
                'work_date' => $attendance->work_date->format('Y-m-d'),
                'comment'   => '修正テスト',
            ]);
        $response->assertRedirect(route('attendance.detail', ['id' => $attendance->id]));
        $response->assertSessionHasErrors([
            'break_in.0'  => '休憩開始と休憩終了はセットで入力してください',
        ]);
    }


    /** 「承認待ち」にログインユーザーが行った申請が全て表示されている */
    public function test_user_can_see_all_their_pending_requests()
    {
        /** @var User $user */
        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->actingAs($user);
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => today(),
            'clock_in' => '09:00',
            'clock_out' => '18:00',
        ]);
        CorrectionRequest::factory()->create([
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'status' => 'pending',
            'comment' => '修正1',
        ]);

        $response = $this->get(route('stamp.request.list'));
        $response->assertStatus(200);
        $response->assertSeeText('修正1');

    }


    /** 「承認済み」に管理者が承認した修正申請が全て表示されている */
    public function test_user_can_see_all_their_approved_requests()
    {
        /** @var User $user */
        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->actingAs($user);
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => today(),
        ]);
        CorrectionRequest::factory()->create([
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'status' => 'approved',
            'comment' => '承認済み1',
        ]);

        $response = $this->get(route('stamp.request.list'));
        $response->assertStatus(200);
        $response->assertSeeText('承認済み1');
    }


    /** 各申請の「詳細」を押下すると勤怠詳細画面に遷移する */
    public function test_clicking_detail_redirects_to_attendance_detail()
    {
        /** @var User $user */
        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->actingAs($user);
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => today(),
            'clock_in' => '09:14:00',
            'clock_out' => '18:00:00',
        ]);
        CorrectionRequest::factory()->create([
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'status' => 'pending',
        ]);

        $listResponse = $this->get(route('stamp.request.list'));
        $listResponse->assertStatus(200);
        $listResponse->assertSee(route('attendance.detail', $attendance->id));

        $detailUrl = route('attendance.detail', $attendance->id);
        $response = $this->get($detailUrl);
        $response->assertStatus(200);
        $response->assertSeeText($attendance->user->name);
        $response->assertSeeText($attendance->work_date->format('Y'));
        $response->assertSeeText($attendance->work_date->format('n月j日'));
    }
}
