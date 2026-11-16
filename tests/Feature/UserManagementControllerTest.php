<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_creates_an_account_with_a_required_password_change(): void
    {
        $admin = User::factory()->admin()->create();
        $studentRole = Role::query()->where('name', 'student')->firstOrFail();

        $response = $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Taylor Reed',
            'email' => 'taylor@example.test',
            'role_id' => $studentRole->id,
        ]);

        $response->assertRedirect(route('users.index'));
        $user = User::query()->where('email', 'taylor@example.test')->firstOrFail();
        $this->assertTrue(Hash::check('Password123', $user->password));
        $this->assertTrue($user->must_change_password);
        $this->assertTrue($user->hasRole('student'));
        $this->assertDatabaseHas('role_user', [
            'user_id' => $user->id,
            'role_id' => $studentRole->id,
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('settings'));
    }

    public function test_non_admin_cannot_manage_user_accounts(): void
    {
        $registrar = User::factory()->create(['role' => 'registrar']);

        $this->actingAs($registrar)
            ->get(route('users.index'))
            ->assertForbidden();
    }

    public function test_admin_cannot_remove_their_last_admin_role(): void
    {
        $admin = User::factory()->admin()->create();
        $registrarRole = Role::query()->where('name', 'registrar')->firstOrFail();

        $this->actingAs($admin)
            ->put(route('users.update-role', $admin), ['role_id' => $registrarRole->id])
            ->assertSessionHasErrors('role_id');

        $this->assertSame('admin', $admin->fresh()->role);
    }

    public function test_admin_navigation_exposes_system_management_but_not_cashier_work(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(route('users.index'))
            ->assertSee(route('system-settings.edit'))
            ->assertSee(route('audit-logs.index'))
            ->assertDontSee(route('students.index'))
            ->assertDontSee(route('courses.index'))
            ->assertDontSee(route('grades.index'))
            ->assertDontSee(route('lecturers.index'))
            ->assertDontSee(route('payments.index'))
            ->assertDontSee(route('cash-management.index'));
    }
}
