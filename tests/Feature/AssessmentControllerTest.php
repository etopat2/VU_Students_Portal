<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\Course;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Institution;
use App\Models\Lecturer;
use App\Models\Module;
use App\Models\Session;
use App\Models\TeachingAllocation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AssessmentControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_assigned_lecturer_can_schedule_an_assessment_with_questions_and_view_results(): void
    {
        Storage::fake('local');
        [$lecturerUser, $module, $session] = $this->createAssignedModule();
        $startsAt = now()->addHour()->format('Y-m-d\TH:i');
        $endsAt = now()->addHours(2)->format('Y-m-d\TH:i');
        $this->actingAs($lecturerUser);

        $this->post(route('assessments.store', ['module' => $module, 'session' => $session]), [
            'name' => 'Coursework test',
            'assessment_type' => 'test',
            'max_score' => '50.00',
            'weight_percentage' => '40.00',
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'question_file' => UploadedFile::fake()->createWithContent('questions.pdf', '%PDF-1.4 test questions'),
        ])->assertRedirect();

        $assessment = Assessment::query()->firstOrFail();
        $this->assertDatabaseHas('assessments', [
            'module_id' => $module->id,
            'session_id' => $session->id,
            'lecturer_id' => $lecturerUser->lecturer->id,
            'name' => 'Coursework test',
            'weight_percentage' => '40.00',
        ]);
        $this->assertSame($startsAt, $assessment->starts_at->format('Y-m-d\TH:i'));
        Storage::disk('local')->assertExists($assessment->question_file_path);

        $this->get(route('assessments.index'))
            ->assertOk()
            ->assertSee($module->title)
            ->assertSee('Coursework test')
            ->assertSee(route('assessments.results.index', $assessment));
    }

    public function test_admin_cannot_access_lecturer_assessment_pages(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('assessments.index'))
            ->assertForbidden();
    }

    public function test_assessment_rejects_an_end_time_before_its_start_time(): void
    {
        Storage::fake('local');
        [$lecturerUser, $module, $session] = $this->createAssignedModule();

        $this->actingAs($lecturerUser)
            ->post(route('assessments.store', ['module' => $module, 'session' => $session]), [
                'name' => 'Invalidly scheduled test',
                'assessment_type' => 'test',
                'max_score' => '50.00',
                'weight_percentage' => '40.00',
                'starts_at' => now()->addHours(2)->format('Y-m-d\TH:i'),
                'ends_at' => now()->addHour()->format('Y-m-d\TH:i'),
                'question_file' => UploadedFile::fake()->createWithContent('questions.pdf', '%PDF-1.4 questions'),
            ])
            ->assertSessionHasErrors('ends_at');

        $this->assertDatabaseMissing('assessments', ['name' => 'Invalidly scheduled test']);
    }

    public function test_assessment_creation_requires_a_schedule_and_question_document(): void
    {
        [$lecturerUser, $module, $session] = $this->createAssignedModule();

        $this->actingAs($lecturerUser)
            ->post(route('assessments.store', ['module' => $module, 'session' => $session]), [
                'name' => 'Unscheduled test',
                'assessment_type' => 'test',
                'max_score' => '50.00',
                'weight_percentage' => '40.00',
            ])
            ->assertSessionHasErrors(['starts_at', 'ends_at', 'question_file']);

        $this->assertDatabaseMissing('assessments', ['name' => 'Unscheduled test']);
    }

    public function test_assigned_lecturer_can_replace_question_file_and_reschedule_an_assessment(): void
    {
        Storage::fake('local');
        [$lecturerUser, $module, $session] = $this->createAssignedModule();
        $previousQuestionPath = 'assessment-questions/previous.pdf';
        Storage::disk('local')->put($previousQuestionPath, '%PDF-1.4 previous');
        $assessment = Assessment::create([
            'module_id' => $module->id,
            'session_id' => $session->id,
            'lecturer_id' => $lecturerUser->lecturer->id,
            'name' => 'Previous examination',
            'assessment_type' => 'final_exam',
            'max_score' => '100.00',
            'weight_percentage' => '100.00',
            'starts_at' => now()->addHour(),
            'ends_at' => now()->addHours(2),
            'question_file_path' => $previousQuestionPath,
            'question_file_original_name' => 'previous.pdf',
        ]);
        $startsAt = now()->addHours(3)->format('Y-m-d\TH:i');
        $endsAt = now()->addHours(4)->format('Y-m-d\TH:i');

        $this->actingAs($lecturerUser)
            ->put(route('assessments.update', $assessment), [
                'name' => 'Rescheduled examination',
                'assessment_type' => 'final_exam',
                'max_score' => '100.00',
                'weight_percentage' => '100.00',
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'question_file' => UploadedFile::fake()->createWithContent('replacement.pdf', '%PDF-1.4 replacement'),
            ])
            ->assertRedirect();

        $assessment->refresh();

        $this->assertSame('Rescheduled examination', $assessment->name);
        $this->assertSame($startsAt, $assessment->starts_at->format('Y-m-d\TH:i'));
        Storage::disk('local')->assertMissing($previousQuestionPath);
        Storage::disk('local')->assertExists($assessment->question_file_path);
    }

    public function test_registrar_can_open_assessments_select_an_assigned_lecturer_and_enter_results(): void
    {
        Storage::fake('local');
        [, $module, $session] = $this->createAssignedModule();
        $lecturer = Lecturer::query()->firstOrFail();
        $registrar = User::factory()->create(['role' => 'registrar']);
        $this->actingAs($registrar);

        $this->get(route('assessments.index'))
            ->assertOk()
            ->assertSee('Tests &amp; examinations', false)
            ->assertSee(route('assessments.index'))
            ->assertSee($module->title)
            ->assertSee('Assigned lecturer')
            ->assertSee($lecturer->name);

        $this->post(route('assessments.store', ['module' => $module, 'session' => $session]), [
            'lecturer_id' => $lecturer->id,
            'name' => 'Registrar-created final',
            'assessment_type' => 'final_exam',
            'max_score' => '100.00',
            'weight_percentage' => '60.00',
            'starts_at' => now()->addHour()->format('Y-m-d\TH:i'),
            'ends_at' => now()->addHours(2)->format('Y-m-d\TH:i'),
            'question_file' => UploadedFile::fake()->createWithContent('final-questions.pdf', '%PDF-1.4 final questions'),
        ])->assertRedirect();

        $assessment = Assessment::query()->firstOrFail();
        $this->assertDatabaseHas('assessments', [
            'id' => $assessment->id,
            'lecturer_id' => $lecturer->id,
            'name' => 'Registrar-created final',
        ]);

        $this->get(route('assessments.results.index', $assessment))
            ->assertOk()
            ->assertSee('Enter assessment results');
    }

    public function test_unassigned_lecturer_cannot_create_an_assessment_for_another_module(): void
    {
        [, $module, $session] = $this->createAssignedModule();
        $unassignedLecturer = User::factory()->create(['role' => 'lecturer']);

        $this->actingAs($unassignedLecturer)
            ->post(route('assessments.store', ['module' => $module, 'session' => $session]), [
                'name' => 'Unauthorized test',
                'assessment_type' => 'test',
                'max_score' => '50.00',
                'weight_percentage' => '40.00',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('assessments', ['name' => 'Unauthorized test']);
    }

    /**
     * @return array{User, Module, Session}
     */
    private function createAssignedModule(): array
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
        $lecturerUser = User::factory()->create(['role' => 'lecturer']);
        $lecturer = Lecturer::create([
            'lecturer_number' => 'LEC-001',
            'name' => 'Dr. Morgan',
            'email' => 'lecturer@example.test',
            'department' => 'Computing',
            'qualification' => 'MSc',
            'status' => 'Active',
            'user_id' => $lecturerUser->id,
            'department_id' => $department->id,
        ]);
        TeachingAllocation::create([
            'lecturer_id' => $lecturer->id,
            'module_id' => $module->id,
            'session_id' => $session->id,
        ]);

        return [$lecturerUser, $module, $session];
    }
}
