<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffAuthenticationTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */

    use RefreshDatabase;

    /* 名前が未入力の場合、バリデーションメッセージが表示される */
    public function test_register_name_error()
    {
        $response = $this->post('/register', [
            'name' => '',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'staff',
        ]);

        $response->assertSessionHasErrors(['name' => 'お名前を入力してください']);
    }

    /* メールアドレスが未入力の場合、バリデーションメッセージが表示される */
    public function test_register_email_error()
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => '',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'staff',
        ]);

        $response->assertSessionHasErrors(['email' => 'メールアドレスを入力してください']);
    }

    /* パスワードが8文字未満の場合、バリデーションメッセージが表示される */
    public function test_register_password_error()
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => '',
            'password' => 'pass',
            'password_confirmation' => 'password',
            'role' => 'staff',
        ]);

        $response->assertSessionHasErrors(['password' => 'パスワードは8文字以上で入力してください']);
    }

    /* パスワードが一致しない場合、バリデーションメッセージが表示される */
    public function test_register_password_confirmation_error()
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => '',
            'password' => 'password',
            'password_confirmation' => 'different_password',
            'role' => 'staff',
        ]);

        $response->assertSessionHasErrors(['password_confirmation' => 'パスワードと一致しません']);
    }

    /* パスワードが未入力の場合、バリデーションメッセージが表示される */
    public function test_register_password_empty_error()
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => '',
            'password' => '',
            'password_confirmation' => 'password',
            'role' => 'staff',
        ]);

        $response->assertSessionHasErrors(['password' => 'パスワードを入力してください']);
    }

    /* フォームに内容が入力されていた場合、データが正常に保存される */
    public function test_register_success()
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'staff',
        ]);

        $this->assertDatabaseHas('users', [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $user = User::where('email', 'test@example.com')->first();
        $user->markEmailAsVerified();

        $this->actingAs($user)->get('/attendance')->assertStatus(200);
    }
}
