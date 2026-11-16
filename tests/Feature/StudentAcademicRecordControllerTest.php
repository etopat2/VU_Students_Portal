<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Course;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\FinancialLedger;
use App\Models\Institution;
use App\Models\Module;
use App\Models\SemesterRegistration;
use App\Models\Session;
use App\Models\Student;
use App\Models\StudentModuleRegistration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentAcademicRecordControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_cannot_view_a_permit_or_results_until_the_ledger_is_cleared(): void
    {
        $user = User::factory()->create(['role' => 'student']);
        $course = Course::create([
            'code' => 'BCS 101',
            'title' => 'Computing',
            'department' => 'Computing',
            'instructor' => 'Dr. Morgan',
            'credits' => 4,
            'status' => 'In progress',
        ]);
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
        $student = Student::create([
            'student_number' => 'VU-2026-001',
            'name' => 'Alex Morgan',
            'email' => 'alex@example.test',
            'programme' => 'Computing',
            'year' => 'Year 1',
            'status' => 'Active',
            'course_id' => $course->id,
        ]);
        $student->user()->associate($user)->save();
        $registration = SemesterRegistration::create([
            'student_id' => $student->id,
            'session_id' => $session->id,
            'status' => 'registered',
        ]);
        StudentModuleRegistration::create([
            'semester_registration_id' => $registration->id,
            'module_id' => $module->id,
            'status' => 'registered',
        ]);
        FinancialLedger::create([
            'student_id' => $student->id,
            'debit' => '100.00',
            'credit' => '0.00',
            'balance_after' => '100.00',
            'description' => 'Outstanding tuition',
        ]);

        $this->actingAs($user)
            ->get(route('students.exam-permit', [$student, $session]))
            ->assertForbidden();
        $this->get(route('students.results', [$student, $session]))
            ->assertForbidden();

        FinancialLedger::create([
            'student_id' => $student->id,
            'debit' => '0.00',
            'credit' => '100.00',
            'balance_after' => '0.00',
            'description' => 'Verified tuition payment',
        ]);

        $this->get(route('students.exam-permit', [$student, $session]))
            ->assertOk()
            ->assertJsonPath('financial_clearance', 'cleared')
            ->assertJsonPath('course', 'Computing');
        $this->get(route('students.results', [$student, $session]))
            ->assertOk()
            ->assertJsonPath('session', 'Semester 1')
            ->assertJsonPath('results', []);
    }
}
