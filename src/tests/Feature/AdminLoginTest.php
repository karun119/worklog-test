<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminLoginTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    use RefreshDatabase;

    /** 管理者ログイン時、メールアドレス未入力でバリデーションエラーが出る */
    public function test_admin_login_email_is_required()
    {
        User::factory()->create([
            'admin_status' => 'admin',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->post('/admin/login', [
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors(['email']);
        $this->assertEquals(
            session('errors')->get('email')[0],
            'メールアドレスを入力してください'
        );
    }


    /** 管理者ログイン時、パスワード未入力でバリデーションエラーが出る */
    public function test_admin_login_password_is_required()
    {
        $admin = User::factory()->create([
            'admin_status' => 'admin',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->post('/admin/login', [
            'email' => $admin->email,
        ]);
        $response->assertSessionHasErrors(['password']);
        $this->assertEquals(
            session('errors')->get('password')[0],
            'パスワードを入力してください'
        );
    }


    /** 管理者ログイン時、メールアドレスまたはパスワードが一致しない場合にエラー */
    public function test_admin_login_invalid_credentials_show_error()
    {
        User::factory()->create([
            'admin_status' => 'admin',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->post('/admin/login', [
            'email' => 'wrong@example.com',
            'password' => 'password123',
        ]);
        $response->assertSessionHasErrors(['email']);
        $this->assertEquals(
            session('errors')->get('email')[0],
            'ログイン情報が登録されていません'
        );
    }
}
