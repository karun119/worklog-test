<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\CorrectionRequest;
use App\Models\CorrectionBreakTime;
use Carbon\Carbon;


use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;




class AttendanceCorrectionTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */

    use RefreshDatabase;

    // 出勤時間が退勤時間より後になっている場合、エラーメッセージが表示される
    public function test_error_displayed_when_clock_in_after_clock_out()
    {
        /** @var User $user */
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user);

        // 勤怠データをユーザーと同じ work_date で作成
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => today(), // Controller の条件に合わせる
            'clock_in' => '09:00',
            'clock_out' => '17:00',
        ]);

        $response = $this->from(route('attendance.detail', ['id' => $attendance->id])) // 元のページ情報
            ->post(route('attendance.update', ['id' => $attendance->id]), [
                'clock_in'  => '18:00',
                'clock_out' => '09:00',
                'break_in'  => [],
                'break_out' => [],
                'comment'   => 'Test comment',
                'work_date' => $attendance->work_date->format('Y-m-d'),
            ]);

        // リダイレクト先は元ページに戻る
        $response->assertRedirect(route('attendance.detail', ['id' => $attendance->id]));

        // バリデーションエラー確認
        $response->assertSessionHasErrors([
            'clock_out' => '出勤時間もしくは退勤時間が不適切な値です',
        ]);
    }


    // 休憩開始時間が退勤時間より後になっている場合、エラーメッセージが表示される
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

    // 休憩終了時間が退勤時間より後になっている場合、エラーメッセージが表示される
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

    // 備考欄が未入力の場合のエラーメッセージが表示される
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

    // 修正申請処理が実行される
    public function test_correction_request_is_created_with_break_times()
    {
        /** @var User $user */
        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->actingAs($user);

        // 勤怠データ作成（出勤:09:00 退勤:18:00）
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => today(),
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'comment' => '通常勤務',
        ]);

        // 休憩時間作成（12:00〜13:00）
        BreakTime::factory()->create([
            'attendance_id' => $attendance->id,
            'break_in' => '12:00',
            'break_out' => '13:00',
        ]);

        // 修正申請の POST リクエスト
        $response = $this->post(route('attendance.update', ['id' => $attendance->id]), [
            'clock_in'  => '09:30',
            'clock_out' => '18:30',
            'break_in'  => ['12:30'],
            'break_out' => ['13:30'],
            'comment'   => '修正申請テスト',
            'work_date' => $attendance->work_date->format('Y-m-d'),
        ]);

        // 勤怠一覧ページへリダイレクトされる
        $response->assertRedirect(route('attendance.list', ['month' => $attendance->work_date->format('Y-m')]));

        // 修正申請が作成されているか確認
        $correctionRequest = CorrectionRequest::where('attendance_id', $attendance->id)
            ->where('user_id', $user->id)
            ->first();

        $this->assertNotNull($correctionRequest, 'CorrectionRequest が作成されていません');
        $this->assertEquals('修正申請テスト', $correctionRequest->comment);
        $this->assertEquals('pending', $correctionRequest->status);
        $this->assertEquals('09:30:00', $correctionRequest->new_clock_in->format('H:i:s'));
        $this->assertEquals('18:30:00', $correctionRequest->new_clock_out->format('H:i:s'));
        $this->assertEquals($attendance->work_date->format('Y-m-d'), $correctionRequest->new_date->format('Y-m-d'));

        // 修正休憩時間が正しく保存されているか確認
        $correctionBreak = CorrectionBreakTime::where('correction_request_id', $correctionRequest->id)->first();
        $this->assertNotNull($correctionBreak, 'CorrectionBreakTime が作成されていません');
        $this->assertEquals('12:30:00', $correctionBreak->new_break_in->format('H:i:s'));
        $this->assertEquals('13:30:00', $correctionBreak->new_break_out->format('H:i:s'));

        // 4. 管理者ユーザーで承認画面確認
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

        // 5. 管理者申請一覧画面確認（承認待ちのみ）
        $adminListResponse = $this->get(route('admin.request.list'));
        $adminListResponse->assertStatus(200);

        // コントローラの view データを取得
        $viewData = $adminListResponse->original->getData();

        // pendingRequests に今回作成した修正申請が含まれていることを確認
        $this->assertTrue(
            $viewData['pendingRequests']->contains(fn($req) => $req->id === $correctionRequest->id),
            '承認待ちタブにCorrectionRequestが存在しません'
        );
    }


    // 補足：
    // 仕様書に明記はありませんが、勤怠データの整合性保持のため
    // 出勤・退勤時間は必須バリデーションを追加しています。
    // そのため、未入力時にエラーが返ることも本テストで確認しています。
    public function test_clock_in。and_clock_out_are_required()
    {
        /** @var User $user */
        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->actingAs($user);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => today(),
        ]);

        // clock_inだけ空
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

        // clock_outだけ空
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
    // 「承認待ち」にログインユーザーが行った申請が全て表示されていること
    public function test_user_can_see_all_their_pending_requests()
    {
        /** @var User $user */
        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->actingAs($user);

        // 勤怠データ作成
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => today(),
            'clock_in' => '09:00',
            'clock_out' => '18:00',
        ]);

        // 修正申請作成（pending）
        CorrectionRequest::factory()->create([
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'status' => 'pending',
            'comment' => '修正1',
        ]);

        $response = $this->get(route('stamp.request.list'));

        $response->assertStatus(200);

        // 承認待ちタブに自分の申請が全て表示されている
        $response->assertSeeText('修正1');

    }

    // 「承認済み」に管理者が承認した修正申請が全て表示されている
    public function test_user_can_see_all_their_approved_requests()
    {
        /** @var User $user */
        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->actingAs($user);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => today(),
        ]);

        // 承認済み申請
        CorrectionRequest::factory()->create([
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'status' => 'approved',
            'comment' => '承認済み1',
        ]);


        $response = $this->get(route('stamp.request.list'));

        $response->assertStatus(200);

        // Blade では承認済みテーブルは JS で切替表示されるため、データ自体は含まれている
        $response->assertSeeText('承認済み1');
   
    }


    // 各申請の「詳細」を押下すると勤怠詳細画面に遷移する
    public function test_clicking_detail_redirects_to_attendance_detail()
    {
        /** @var User $user */
        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->actingAs($user);

        // 勤怠データ作成
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => today(),
            'clock_in' => '09:14:00',
            'clock_out' => '18:00:00',
        ]);

        // 修正申請データ作成（承認待ち）
        CorrectionRequest::factory()->create([
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'status' => 'pending',
        ]);

        // 申請一覧ページにアクセス
        $listResponse = $this->get(route('stamp.request.list'));
        $listResponse->assertStatus(200);

        // 申請一覧に勤怠詳細へのリンクがあることを確認
        $listResponse->assertSee(route('attendance.detail', $attendance->id));

        // 「詳細」ボタンを押下 → 勤怠詳細画面に遷移
        $detailUrl = route('attendance.detail', $attendance->id);
        $response = $this->get($detailUrl);

        // 遷移確認
        $response->assertStatus(200);

        // Blade 出力確認（ユーザー名と日付のみ）
        $response->assertSeeText($attendance->user->name);
        $response->assertSeeText($attendance->work_date->format('Y'));
        $response->assertSeeText($attendance->work_date->format('n月j日'));
    }
}
