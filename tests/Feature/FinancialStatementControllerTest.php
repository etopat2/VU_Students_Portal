<?php

namespace Tests\Feature;

use App\Models\FinancialLedger;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancialStatementControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_view_their_own_financial_statement(): void
    {
        $user = User::factory()->create(['role' => 'student']);
        $student = Student::create([
            'student_number' => 'VU-2026-001',
            'name' => 'Alex Morgan',
            'email' => 'alex@example.test',
            'programme' => 'Information Technology',
            'year' => 'Year 1',
            'status' => 'Active',
        ]);
        $student->user()->associate($user)->save();
        FinancialLedger::create([
            'student_id' => $student->id,
            'debit' => '150.00',
            'credit' => '0.00',
            'balance_after' => '150.00',
            'description' => 'Semester invoice',
            'posted_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('financial-statements.mine'))
            ->assertOk()
            ->assertSee('Alex Morgan')
            ->assertSee('Semester invoice')
            ->assertSee('150.00');
    }

    public function test_only_accountants_and_registrars_can_open_the_statements_directory(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'student']))
            ->get(route('financial-statements.index'))
            ->assertForbidden();
    }
}
