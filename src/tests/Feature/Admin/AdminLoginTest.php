<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;

class AdminLoginTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */

    use RefreshDatabase;

    /* メールアドレスが未入力の場合、バリデーションメッセージが表示される */
    public function test_admin_login_email_error()
    {
        $response = $this->post('/login', [
            'email' => '',
            'password' => 'password',
            'login_type' => 'admin',
        ]);
        $response->assertSessionHasErrors(['email' => 'メールアドレスを入力してください']);
    }

    /* パスワードが未入力の場合、バリデーションメッセージが表示される */
    public function test_admin_login_password_error()
    {
        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => '',
            'login_type' => 'admin',
        ]);

        $response->assertSessionHasErrors(['password' => 'パスワードを入力してください']);
    }

    /* 登録内容と一致しない場合、バリデーションメッセージが表示される */
    public function test_admin_login_invalid_credentials()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('correctpassword'),
            'role' => 'admin',
        ]);

        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
            'login_type' => 'admin',
        ]);

        $response->assertSessionHasErrors(['email' => 'ログイン情報が登録されていません']);
    }
}
