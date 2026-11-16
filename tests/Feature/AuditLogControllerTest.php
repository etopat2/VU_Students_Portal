<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_filter_audit_logs_by_user_action_and_date(): void
    {
        $admin = User::factory()->admin()->create();
        $actor = User::factory()->create(['role' => 'accountant']);
        AuditLog::create([
            'user_id' => $actor->id,
            'action' => 'payment.created',
            'subject_type' => 'App\\Models\\Payment',
            'subject_id' => 12,
            'new_values' => ['amount' => '25.00'],
            'ip_address' => '127.0.0.1',
            'created_at' => '2026-11-13 10:00:00',
        ]);
        AuditLog::create([
            'user_id' => $admin->id,
            'action' => 'user.updated',
            'subject_type' => 'App\\Models\\User',
            'subject_id' => 13,
            'created_at' => '2026-11-12 10:00:00',
        ]);

        $this->actingAs($admin)
            ->get(route('audit-logs.index', [
                'action' => 'payment.created',
                'user_id' => $actor->id,
                'date' => '2026-11-13',
            ]))
            ->assertOk()
            ->assertSee('payment.created')
            ->assertSee('25.00')
            ->assertDontSee('user.updated');
    }

    public function test_non_admin_cannot_view_audit_logs(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'accountant']))
            ->get(route('audit-logs.index'))
            ->assertForbidden();
    }
}
