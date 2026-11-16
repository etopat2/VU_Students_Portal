<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Course;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\FeeStructure;
use App\Models\FinancialLedger;
use App\Models\Institution;
use App\Models\Module;
use App\Models\Session;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancialWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_invoices_the_student_and_verified_payments_update_clearance(): void
    {
        $registrar = User::factory()->create(['role' => 'registrar']);
        $accountant = User::factory()->create(['role' => 'accountant']);
        $student = Student::create([
            'student_number' => 'VU-2026-001',
            'name' => 'Alex Morgan',
            'email' => 'alex@example.test',
            'programme' => 'Computing',
            'year' => 'Year 1',
            'status' => 'Active',
        ]);
        [, $session, $module] = $this->createAcademicOffer($student);

        $this->actingAs(User::factory()->create(['role' => 'student']))
            ->post(route('payments.store'))
            ->assertForbidden();
        $this->assertDatabaseCount('payments', 0);

        $this->actingAs($registrar);

        $registrationResponse = $this->post(route('semester-registrations.store', $student), [
            'session_id' => $session->id,
            'module_ids' => [$module->id],
        ]);

        $registrationResponse->assertRedirect();
        $this->assertDatabaseHas('semester_registrations', [
            'student_id' => $student->id,
            'session_id' => $session->id,
        ]);
        $this->assertDatabaseHas('invoices', [
            'student_id' => $student->id,
            'amount' => '150.00',
            'status' => 'issued',
        ]);
        $this->assertDatabaseHas('financial_ledgers', [
            'student_id' => $student->id,
            'debit' => '150.00',
            'credit' => '0.00',
            'balance_after' => '150.00',
        ]);
        $this->assertDatabaseHas('students', [
            'id' => $student->id,
            'clearance_status' => 'uncleared',
        ]);
        $this->assertFalse($student->fresh()->isFinanciallyCleared());

        $invoiceId = $student->invoices()->value('id');
        $this->actingAs($accountant);
        $this->get(route('payments.index'))
            ->assertOk()
            ->assertSee($student->name)
            ->assertSee('150.00');

        $this->post(route('payments.store'), [
            'invoice_id' => $invoiceId,
            'amount' => '149.99',
            'method' => 'bank_transfer',
            'reference' => 'PAYMENT-001',
        ])->assertRedirect();

        $this->assertDatabaseHas('invoices', [
            'id' => $invoiceId,
            'status' => 'partially_paid',
        ]);
        $this->assertDatabaseHas('financial_ledgers', [
            'student_id' => $student->id,
            'debit' => '0.00',
            'credit' => '149.99',
            'balance_after' => '0.01',
        ]);

        $this->from(route('dashboard'))
            ->post(route('payments.store'), [
                'invoice_id' => $invoiceId,
                'amount' => '0.02',
                'method' => 'bank_transfer',
            ])
            ->assertRedirect(route('dashboard'))
            ->assertSessionHasErrors('amount');

        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseCount('financial_ledgers', 2);

        $this->post(route('payments.store'), [
            'invoice_id' => $invoiceId,
            'amount' => '0.01',
            'method' => 'bank_transfer',
            'reference' => 'PAYMENT-002',
        ])->assertRedirect();

        $this->assertDatabaseHas('invoices', [
            'id' => $invoiceId,
            'status' => 'paid',
        ]);
        $this->assertDatabaseHas('financial_ledgers', [
            'student_id' => $student->id,
            'balance_after' => '0.00',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $accountant->id,
            'action' => 'payment.created',
            'subject_type' => \App\Models\Payment::class,
        ]);
        $this->assertDatabaseHas('students', [
            'id' => $student->id,
            'clearance_status' => 'cleared',
        ]);
        $this->assertTrue($student->fresh()->isFinanciallyCleared());
    }

    /**
     * @return array{Course, Session, Module}
     */
    private function createAcademicOffer(Student $student): array
    {
        $course = Course::create([
            'code' => 'BCS 101',
            'title' => 'Computing',
            'department' => 'Computing',
            'instructor' => 'Dr. Morgan',
            'credits' => 4,
            'status' => 'In progress',
        ]);
        $student->update(['course_id' => $course->id]);
        $institution = Institution::create(['name' => 'Example University', 'code' => 'EXU']);
        $faculty = Faculty::create([
            'institution_id' => $institution->id,
            'name' => 'Science',
            'code' => 'SCI',
        ]);
        $department = Department::create([
            'faculty_id' => $faculty->id,
            'name' => 'Computing',
            'code' => 'COMP',
        ]);
        $academicYear = AcademicYear::create([
            'course_id' => $course->id,
            'name' => 'Year 1',
            'year_level' => 1,
        ]);
        $session = Session::create([
            'academic_year_id' => $academicYear->id,
            'name' => 'Semester 1',
            'number' => 1,
            'is_open' => true,
        ]);
        $module = Module::create([
            'department_id' => $department->id,
            'code' => 'CS101',
            'title' => 'Introduction to Computing',
            'credit_units' => 4,
            'year_level' => 1,
            'session_number' => 1,
        ]);
        $course->modules()->attach($module);
        FeeStructure::create([
            'course_id' => $course->id,
            'academic_year_id' => $academicYear->id,
            'session_id' => $session->id,
            'tuition_amount' => '100.00',
            'functional_fees' => '50.00',
        ]);

        return [$course, $session, $module];
    }
}
