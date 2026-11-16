<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\AssessmentSubmission;
use App\Models\Course;
use App\Models\Department;
use App\Models\Faculty;
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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AssessmentSubmissionControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_registered_student_can_submit_a_formatted_answer_without_executable_markup(): void
    {
        [$studentUser, $assessment, $moduleRegistration] = $this->createRegisteredAssessment();

        $this->actingAs($studentUser)
            ->post(route('student-assessments.submissions.store', $assessment), [
                'answer_content' => '<p><strong>My answer</strong><script>alert(1)</script><img src=x onerror=alert(2)></p>',
            ])
            ->assertRedirect(route('student-assessments.show', $assessment));

        $submission = AssessmentSubmission::query()->firstOrFail();

        $this->assertSame($moduleRegistration->id, $submission->student_module_registration_id);
        $this->assertSame('<p><strong>My answer</strong>alert(1)</p>', $submission->answer_content);
        $this->assertNotNull($submission->submitted_at);

        $this->post(route('student-assessments.submissions.store', $assessment), [
            'answer_content' => '<p>Updated answer</p>',
        ])->assertRedirect(route('student-assessments.show', $assessment));

        $this->assertDatabaseCount('assessment_submissions', 1);
        $this->assertDatabaseHas('assessment_submissions', [
            'id' => $submission->id,
            'answer_content' => '<p>Updated answer</p>',
        ]);
    }

    public function test_student_assessment_pages_only_show_registered_assessments(): void
    {
        [$studentUser, $assessment] = $this->createRegisteredAssessment();

        $this->actingAs($studentUser)
            ->get(route('student-assessments.index'))
            ->assertOk()
            ->assertSee($assessment->name)
            ->assertSee(route('student-assessments.show', $assessment));
    }

    public function test_student_assessment_list_hides_assessments_without_a_schedule_or_question_file(): void
    {
        [$studentUser, $assessment] = $this->createRegisteredAssessment();
        $assessment->update(['question_file_path' => null]);

        $this->actingAs($studentUser)
            ->get(route('student-assessments.index'))
            ->assertOk()
            ->assertDontSee($assessment->name);
    }

    public function test_student_can_open_an_available_assessment_and_see_the_answer_editor(): void
    {
        [$studentUser, $assessment] = $this->createRegisteredAssessment();

        $this->actingAs($studentUser)
            ->get(route('student-assessments.show', $assessment))
            ->assertOk()
            ->assertSee($assessment->name)
            ->assertSee('data-answer-editor', false)
            ->assertSee('Submit answer');
    }

    public function test_registered_student_can_download_questions_and_submit_an_answer_sheet(): void
    {
        Storage::fake('local');
        [$studentUser, $assessment] = $this->createRegisteredAssessment();
        $questionPath = 'assessment-questions/questions.pdf';
        Storage::disk('local')->put($questionPath, '%PDF-1.4 question');
        $assessment->update([
            'question_file_path' => $questionPath,
            'question_file_original_name' => 'final-questions.pdf',
        ]);

        $this->actingAs($studentUser)
            ->get(route('student-assessments.question', $assessment))
            ->assertOk()
            ->assertHeader('content-disposition');

        $this->post(route('student-assessments.submissions.store', $assessment), [
            'answer_file' => UploadedFile::fake()->createWithContent('answers.pdf', '%PDF-1.4 answer sheet'),
        ])->assertRedirect(route('student-assessments.show', $assessment));

        $submission = AssessmentSubmission::query()->firstOrFail();

        Storage::disk('local')->assertExists($submission->answer_file_path);

        $this->get(route('student-assessments.answer', $assessment))
            ->assertOk()
            ->assertHeader('content-disposition');
    }

    public function test_student_cannot_submit_after_the_assessment_deadline(): void
    {
        [$studentUser, $assessment] = $this->createRegisteredAssessment([
            'ends_at' => now()->subMinute(),
        ]);

        $this->actingAs($studentUser)
            ->post(route('student-assessments.submissions.store', $assessment), [
                'answer_content' => '<p>Too late</p>',
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('assessment_submissions', 0);
    }

    public function test_student_cannot_download_questions_before_the_assessment_starts(): void
    {
        Storage::fake('local');
        [$studentUser, $assessment] = $this->createRegisteredAssessment([
            'starts_at' => now()->addHour(),
        ]);
        $questionPath = 'assessment-questions/scheduled.pdf';
        Storage::disk('local')->put($questionPath, '%PDF-1.4 question');
        $assessment->update([
            'question_file_path' => $questionPath,
            'question_file_original_name' => 'scheduled.pdf',
        ]);

        $this->actingAs($studentUser)
            ->get(route('student-assessments.question', $assessment))
            ->assertForbidden();
    }

    public function test_student_cannot_download_questions_after_the_submission_window_closes(): void
    {
        Storage::fake('local');
        [$studentUser, $assessment] = $this->createRegisteredAssessment([
            'ends_at' => now()->subMinute(),
        ]);
        $questionPath = 'assessment-questions/closed.pdf';
        Storage::disk('local')->put($questionPath, '%PDF-1.4 closed questions');
        $assessment->update([
            'question_file_path' => $questionPath,
            'question_file_original_name' => 'closed.pdf',
        ]);

        $this->actingAs($studentUser)
            ->get(route('student-assessments.question', $assessment))
            ->assertForbidden();
    }

    public function test_unregistered_student_cannot_open_an_assessment(): void
    {
        [, $assessment] = $this->createRegisteredAssessment();
        $otherStudent = User::factory()->create(['role' => 'student']);

        $this->actingAs($otherStudent)
            ->get(route('student-assessments.show', $assessment))
            ->assertForbidden();
    }

    public function test_invalid_answer_file_is_rejected_without_creating_a_submission(): void
    {
        [$studentUser, $assessment] = $this->createRegisteredAssessment();

        $this->actingAs($studentUser)
            ->post(route('student-assessments.submissions.store', $assessment), [
                'answer_file' => UploadedFile::fake()->createWithContent('answer.php', '<?php echo "not an allowed answer type";'),
            ])
            ->assertSessionHasErrors('answer_file');

        $this->assertDatabaseCount('assessment_submissions', 0);
    }

    public function test_blank_editor_content_cannot_be_submitted_as_an_answer(): void
    {
        [$studentUser, $assessment] = $this->createRegisteredAssessment();

        $this->actingAs($studentUser)
            ->post(route('student-assessments.submissions.store', $assessment), [
                'answer_content' => '<p><br></p>',
            ])
            ->assertSessionHasErrors('answer_content');

        $this->assertDatabaseCount('assessment_submissions', 0);
    }

    public function test_assigned_lecturer_can_view_student_submission_status(): void
    {
        [, $assessment, $moduleRegistration, $lecturerUser] = $this->createRegisteredAssessment();
        AssessmentSubmission::create([
            'assessment_id' => $assessment->id,
            'student_module_registration_id' => $moduleRegistration->id,
            'answer_content' => '<p>Submitted answer</p>',
            'submitted_at' => now(),
        ]);

        $this->actingAs($lecturerUser)
            ->get(route('assessments.submissions.index', $assessment))
            ->assertOk()
            ->assertSee('Submission status')
            ->assertSee('Alex Morgan')
            ->assertSee('Submitted answer')
            ->assertSee('Submitted');
    }

    /**
     * @param array<string, mixed> $assessmentAttributes
     * @return array{User, Assessment, StudentModuleRegistration, User}
     */
    private function createRegisteredAssessment(array $assessmentAttributes = []): array
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
        $studentUser = User::factory()->create(['role' => 'student']);
        $student = Student::create([
            'student_number' => 'VU-2026-001',
            'name' => 'Alex Morgan',
            'email' => 'alex@example.test',
            'programme' => 'Computing',
            'year' => 'Year 1',
            'status' => 'Active',
            'course_id' => $course->id,
        ]);
        $student->user()->associate($studentUser)->save();
        $semesterRegistration = SemesterRegistration::create([
            'student_id' => $student->id,
            'session_id' => $session->id,
            'status' => 'registered',
        ]);
        $moduleRegistration = StudentModuleRegistration::create([
            'semester_registration_id' => $semesterRegistration->id,
            'module_id' => $module->id,
            'status' => 'registered',
        ]);
        $assessment = Assessment::create([
            'module_id' => $module->id,
            'session_id' => $session->id,
            'lecturer_id' => $lecturer->id,
            'name' => 'Final examination',
            'assessment_type' => 'final_exam',
            'max_score' => '100.00',
            'weight_percentage' => '100.00',
            'starts_at' => now()->subMinute(),
            'ends_at' => now()->addHour(),
            'question_file_path' => 'assessment-questions/questions.pdf',
            'question_file_original_name' => 'questions.pdf',
            ...$assessmentAttributes,
        ]);

        return [$studentUser, $assessment, $moduleRegistration, $lecturerUser];
    }
}
