<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\URL;


class UserAuthTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    use RefreshDatabase;

    /** 名前が未入力の場合、バリデーションメッセージを表示 */
    public function test_name_is_required()
    {
        $response = $this->post('/register', [
            'name' => '',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);
        $response->assertSessionHasErrors([
            'name' => 'お名前を入力してください',
        ]);
    }


    /** メールアドレスが未入力の場合、バリデーションメッセージを表示 */
    public function test_email_is_required()
    {
        $response = $this->post('/register', [
            'name' => '山田太郎',
            'email' => '',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);
        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください',
        ]);
    }


    /** パスワードが8文字未満の場合 */
    public function test_password_must_be_at_least_8_characters()
    {
        $response = $this->post('/register', [
            'name' => '山田太郎',
            'email' => 'test@example.com',
            'password' => '1234567',
            'password_confirmation' => '1234567',
        ]);
        $response->assertSessionHasErrors([
            'password' => 'パスワードは8文字以上で入力してください',
        ]);
    }


    /** パスワード不一致 */
    public function test_password_confirmation_must_match()
    {
        $response = $this->post('/register', [
            'name' => '山田太郎',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different',
        ]);
        $response->assertSessionHasErrors([
            'password_confirmation' => 'パスワードと一致しません',
        ]);
    }


    /** パスワード未入力 */
    public function test_password_is_required()
    {
        $response = $this->post('/register', [
            'name' => '山田太郎',
            'email' => 'test@example.com',
            'password' => '',
            'password_confirmation' => '',
        ]);
        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください',
        ]);
    }


    /** 正常に登録できる */
    public function test_user_can_register_successfully()
    {
        $response = $this->post('/register', [
            'name' => '山田太郎',
            'email' => 'yamada@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);
        $this->assertDatabaseHas('users', [
            'email' => 'yamada@example.com',
        ]);
        $this->assertAuthenticated();

        $response->assertRedirect(route('verification.notice'));
    }


    /** ログイン時、メールアドレスが未入力の場合、バリデーションメッセージ */
    public function test_login_email_is_required()
    {
        User::factory()->create([
            'email' => 'yamada@example.com',
            'admin_status' => 'general',
            'email_verified_at' => now(),
            'password' => bcrypt('password123'),
        ]);
        $response = $this->post('/login', [
            'email' => '',
            'password' => 'password123',
        ]);
        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください',
        ]);
    }


    /** ログイン時、パスワードが未入力の場合、バリデーションメッセージ */
    public function test_login_password_is_required()
    {
        User::factory()->create([
            'email' => 'yamada@example.com',
            'admin_status' => 'general',
            'email_verified_at' => now(),
            'password' => bcrypt('password123'),
        ]);
        $response = $this->post('/login', [
            'email' => 'yamada@example.com',
            'password' => '',
        ]);
        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください',
        ]);
    }


    /** 登録内容と一致しない場合、バリデーションメッセージが表示される*/
    public function test_login_fails_with_wrong_credentials()
    {
        User::factory()->create([
            'email' => 'yamada@example.com',
            'admin_status' => 'general',
            'email_verified_at' => now(),
            'password' => bcrypt('password123'),
        ]);
        $response = $this->post('/login', [
            'email' => 'wrong@example.com',
            'password' => 'password123',
        ]);
        $response->assertSessionHasErrors([
            'email' => 'ログイン情報が登録されていません',
        ]);
    }


    /** 会員登録後、認証メールが送信される */
    public function test_verification_email_is_sent_after_register()
    {
        Notification::fake();

        $this->post('/register', [
            'name' => '山田太郎',
            'email' => 'verify@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);
        $user = User::where('email', 'verify@example.com')->first();
        Notification::assertSentTo(
            [$user],
            VerifyEmail::class
        );
    }


    /** メール認証誘導画面でボタンを押すとメール認証サイトに遷移する */
    public function test_verification_notice_button_redirects_to_email_verification_site()
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);
        $user = User::find($user->id);
        $this->actingAs($user);
        $response = $this->get('/email/verify');
        $response->assertStatus(200);
        $response->assertViewIs('auth.verify');
        $response->assertSee('認証はこちらから');
        $response->assertSee('http://localhost:8025');
    }


    /** メール認証サイトで認証を完了すると勤怠画面にリダイレクトされる */
    public function test_email_verification_completes_and_redirects_to_attendance()
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);
        $user = User::find($user->id);
        $this->actingAs($user);
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $response = $this->get($verificationUrl);
        $response->assertRedirect('/attendance');
        $this->assertNotNull($user->fresh()->email_verified_at);
    }
}
