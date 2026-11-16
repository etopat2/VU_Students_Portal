<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\Course;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\GradeScale;
use App\Models\Institution;
use App\Models\Lecturer;
use App\Models\Module;
use App\Models\SemesterRegistration;
use App\Models\Session;
use App\Models\Student;
use App\Models\StudentModuleRegistration;
use App\Models\TeachingAllocation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssessmentResultControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_an_assigned_lecturer_can_record_a_result_and_generate_the_final_mark(): void
    {
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
        $registration = SemesterRegistration::create([
            'student_id' => $student->id,
            'session_id' => $session->id,
            'status' => 'registered',
        ]);
        $moduleRegistration = StudentModuleRegistration::create([
            'semester_registration_id' => $registration->id,
            'module_id' => $module->id,
            'status' => 'registered',
        ]);
        $assignedUser = User::factory()->create(['role' => 'lecturer']);
        $assignedLecturer = Lecturer::create([
            'lecturer_number' => 'LEC-001',
            'name' => 'Dr. Morgan',
            'email' => 'lecturer@example.test',
            'department' => 'Computing',
            'qualification' => 'MSc',
            'status' => 'Active',
            'user_id' => $assignedUser->id,
            'department_id' => $department->id,
        ]);
        $unassignedUser = User::factory()->create(['role' => 'lecturer']);
        Lecturer::create([
            'lecturer_number' => 'LEC-002',
            'name' => 'Dr. Other',
            'email' => 'other@example.test',
            'department' => 'Computing',
            'qualification' => 'MSc',
            'status' => 'Active',
            'user_id' => $unassignedUser->id,
            'department_id' => $department->id,
        ]);
        TeachingAllocation::create([
            'lecturer_id' => $assignedLecturer->id,
            'module_id' => $module->id,
            'session_id' => $session->id,
        ]);
        $assessment = Assessment::create([
            'module_id' => $module->id,
            'session_id' => $session->id,
            'lecturer_id' => $assignedLecturer->id,
            'name' => 'Final examination',
            'assessment_type' => 'final_exam',
            'max_score' => '100.00',
            'weight_percentage' => '100.00',
        ]);
        $this->actingAs($unassignedUser)
            ->get(route('assessments.results.index', $assessment))
            ->assertForbidden();

        $this->actingAs($assignedUser)
            ->get(route('assessments.results.index', $assessment))
            ->assertOk()
            ->assertSee('Alex Morgan')
            ->assertSee('Score / 100.00');

        GradeScale::create([
            'letter_grade' => 'A',
            'minimum_mark' => '70.00',
            'grade_point' => '4.00',
        ]);

        $this->actingAs($unassignedUser)
            ->post(route('assessment-results.store', $assessment), [
                'student_module_registration_id' => $moduleRegistration->id,
                'score' => '80.00',
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('assessment_results', 0);

        $this->actingAs($assignedUser)
            ->post(route('assessment-results.store', $assessment), [
                'student_module_registration_id' => $moduleRegistration->id,
                'score' => '80.00',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('assessment_results', [
            'student_module_registration_id' => $moduleRegistration->id,
            'assessment_id' => $assessment->id,
            'score' => '80.00',
        ]);
        $this->assertDatabaseHas('final_module_results', [
            'student_module_registration_id' => $moduleRegistration->id,
            'coursework_score' => '0.00',
            'exam_score' => '80.00',
            'final_mark' => '80.00',
            'letter_grade' => 'A',
            'grade_point' => '4.00',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $assignedUser->id,
            'action' => 'assessment_result.created',
            'subject_type' => \App\Models\AssessmentResult::class,
        ]);
    }
}
