<?php

namespace Tests\Feature;

use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AccountControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_profile_and_settings_editor(): void
    {
        $admin = User::factory()->admin()->create([
            'name' => 'Portal Administrator',
            'email' => 'admin@example.test',
        ]);

        $this->actingAs($admin)
            ->get(route('profile'))
            ->assertOk()
            ->assertSee('Administrator profile')
            ->assertSee('admin@example.test');

        $this->get(route('settings'))
            ->assertOk()
            ->assertSee('Portal settings')
            ->assertSee('action="'.route('settings.update').'"', false)
            ->assertSee('name="current_password"', false)
            ->assertSee('name="password_confirmation"', false);
    }

    public function test_user_can_update_their_account_and_linked_student_details_without_changing_role(): void
    {
        $user = User::factory()->create([
            'name' => 'Student Name',
            'email' => 'student@example.test',
            'role' => 'student',
        ]);
        $student = Student::create([
            'student_number' => 'VU-2026-001',
            'name' => 'Student Name',
            'email' => 'student@example.test',
            'programme' => 'Business Computing',
            'year' => 'Year 1',
            'status' => 'Active',
        ]);
        $student->user()->associate($user)->save();

        $this->actingAs($user)
            ->put(route('settings.update'), [
                'name' => 'Updated Student',
                'email' => 'updated@example.test',
                'role' => 'admin',
            ])
            ->assertRedirect(route('settings'))
            ->assertSessionHas('status', 'Your account details have been updated.');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Student',
            'email' => 'updated@example.test',
            'role' => 'student',
        ]);
        $this->assertDatabaseHas('students', [
            'id' => $student->id,
            'name' => 'Updated Student',
            'email' => 'updated@example.test',
        ]);
    }

    public function test_user_can_change_password_with_their_current_password(): void
    {
        $user = User::factory()->admin()->create([
            'password' => 'old-password',
        ]);

        $this->actingAs($user)
            ->put(route('settings.update'), [
                'name' => $user->name,
                'email' => $user->email,
                'current_password' => 'old-password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])
            ->assertRedirect(route('settings'));

        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
    }

    public function test_invalid_current_password_does_not_change_account_details(): void
    {
        $user = User::factory()->admin()->create([
            'password' => 'old-password',
        ]);

        $this->actingAs($user)
            ->from(route('settings'))
            ->put(route('settings.update'), [
                'name' => 'Changed Name',
                'email' => 'changed@example.test',
                'current_password' => 'wrong-password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])
            ->assertRedirect(route('settings'))
            ->assertSessionHasErrors('current_password');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ]);
        $this->assertFalse(Hash::check('new-password', $user->fresh()->password));
    }

    public function test_user_cannot_change_email_to_another_accounts_email(): void
    {
        $user = User::factory()->admin()->create();
        User::factory()->create(['email' => 'taken@example.test']);

        $this->actingAs($user)
            ->from(route('settings'))
            ->put(route('settings.update'), [
                'name' => $user->name,
                'email' => 'taken@example.test',
            ])
            ->assertRedirect(route('settings'))
            ->assertSessionHasErrors('email');

        $this->assertSame($user->email, $user->fresh()->email);
    }
}
