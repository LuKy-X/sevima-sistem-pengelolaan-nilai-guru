<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 1. Guest cannot access dashboard.
     */
    public function test_guest_cannot_access_dashboard(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect('/login');
    }

    /**
     * 2. Valid teacher credentials can log in.
     */
    public function test_valid_teacher_credentials_can_log_in(): void
    {
        $teacher = User::factory()->create([
            'email' => 'guru@sekolah.id',
            'password' => Hash::make('password'),
        ]);

        $response = $this->post('/login', [
            'email' => 'guru@sekolah.id',
            'password' => 'password',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($teacher);
    }

    /**
     * 3. Invalid credentials are rejected.
     */
    public function test_invalid_credentials_are_rejected(): void
    {
        User::factory()->create([
            'email' => 'guru@sekolah.id',
            'password' => Hash::make('password'),
        ]);

        $response = $this->from('/login')->post('/login', [
            'email' => 'guru@sekolah.id',
            'password' => 'wrong-password',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    /**
     * 4. Authenticated teacher can access dashboard.
     */
    public function test_authenticated_teacher_can_access_dashboard(): void
    {
        $teacher = User::factory()->create([
            'name' => 'Budi Santoso, S.Kom.',
            'nip' => '198507152010011012',
        ]);

        $response = $this->actingAs($teacher)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Budi Santoso, S.Kom.');
        $response->assertSee('198507152010011012');
    }

    /**
     * 5. Teacher can log out.
     */
    public function test_teacher_can_log_out(): void
    {
        $teacher = User::factory()->create();

        $response = $this->actingAs($teacher)->post('/logout');

        $response->assertRedirect('/login');
        $this->assertGuest();
    }
}
