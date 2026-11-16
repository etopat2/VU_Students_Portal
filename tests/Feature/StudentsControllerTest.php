<?php

namespace Tests\Feature;

use App\Models\Student;
use App\Models\Course;
use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\StudyProgram;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_be_created_with_valid_details(): void
    {
        [$studyProgram, $course, $academicYear] = $this->createStudentSelection('BCS 101');
        $this->actingAs(User::factory()->create(['role' => 'registrar']));
        $response = $this->post(route('students.store'), [
            'student_number' => 'VU-2026-001',
            'name' => 'Alex Morgan',
            'email' => 'alex@example.test',
            'status' => 'Active',
            'study_program_id' => $studyProgram->id,
            'course_id' => $course->id,
            'academic_year_id' => $academicYear->id,
        ]);

        $response->assertRedirect(route('students.index'));
        $response->assertSessionHas('laravel-toaster-magic::messages', fn (array $messages): bool => $messages[0]['type'] === 'success'
            && $messages[0]['message'] === 'Student added successfully.');
        $this->assertDatabaseHas('students', [
            'student_number' => 'VU-2026-001',
            'name' => 'Alex Morgan',
            'email' => 'alex@example.test',
            'study_program_id' => $studyProgram->id,
            'course_id' => $course->id,
            'academic_year_id' => $academicYear->id,
        ]);

        $this->get(route('students.index'))
            ->assertOk()
            ->assertSee('Day')
            ->assertSee('Computing BCS 101')
            ->assertSee('Year 1');
    }

    public function test_student_updates_and_deletes_through_resource_routes(): void
    {
        [$studyProgram, $course, $academicYear] = $this->createStudentSelection('BCS 101');
        $this->actingAs(User::factory()->create(['role' => 'registrar']));
        $student = Student::create([
            'student_number' => 'VU-2026-001',
            'name' => 'Alex Morgan',
            'email' => 'alex@example.test',
            'programme' => $course->title,
            'year' => 'Year 1',
            'status' => 'Active',
            'study_program_id' => $studyProgram->id,
            'course_id' => $course->id,
            'academic_year_id' => $academicYear->id,
        ]);

        $this->put(route('students.update', $student), [
            'student_number' => 'VU-2026-001',
            'name' => 'Alex Morgan Updated',
            'email' => 'alex@example.test',
            'status' => 'Active',
            'study_program_id' => $studyProgram->id,
            'course_id' => $course->id,
            'academic_year_id' => $academicYear->id,
        ])->assertRedirect(route('students.index'));

        $this->assertDatabaseHas('students', [
            'id' => $student->id,
            'name' => 'Alex Morgan Updated',
            'programme' => $course->title,
        ]);

        $this->delete(route('students.destroy', $student))
            ->assertRedirect(route('students.index'));

        $this->assertDatabaseMissing('students', ['id' => $student->id]);
    }

    public function test_student_creation_rejects_missing_required_details(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'registrar']));
        $this->from(route('students.create'))
            ->post(route('students.store'), [])
            ->assertRedirect(route('students.create'))
            ->assertSessionHasErrors([
                'student_number',
                'name',
                'email',
                'status',
                'study_program_id',
                'course_id',
                'academic_year_id',
            ]);

        $this->assertDatabaseCount('students', 0);
    }

    public function test_student_creation_rejects_a_program_or_year_from_another_course(): void
    {
        [$studyProgram, $course] = $this->createStudentSelection('BCS 101');
        [$otherProgram, , $otherAcademicYear] = $this->createStudentSelection('BBA 101', null, 'Evening');
        $this->actingAs(User::factory()->create(['role' => 'registrar']));

        $this->from(route('students.create'))
            ->post(route('students.store'), [
                'student_number' => 'VU-2026-003',
                'name' => 'Jordan Smith',
                'email' => 'jordan@example.test',
                'status' => 'Active',
                'study_program_id' => $otherProgram->id,
                'course_id' => $course->id,
                'academic_year_id' => $otherAcademicYear->id,
            ])
            ->assertRedirect(route('students.create'))
            ->assertSessionHasErrors([
                'study_program_id' => 'Choose a study program offered for this course.',
                'academic_year_id' => 'Choose a year of study that belongs to the selected course.',
            ]);

        $this->assertDatabaseMissing('students', ['student_number' => 'VU-2026-003']);
        $this->assertNotSame($studyProgram->id, $otherProgram->id);
    }

    public function test_admin_cannot_manage_student_records(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('students.index'))
            ->assertForbidden();
    }

    public function test_validation_errors_are_shown_as_toasts_without_inline_feedback(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'registrar']));

        $this->from(route('students.create'))
            ->post(route('students.store'), [])
            ->assertRedirect(route('students.create'));

        $this->get(route('students.create'))
            ->assertOk()
            ->assertSee('The student number field is required.')
            ->assertDontSee('invalid-feedback', false);
    }

    public function test_create_and_edit_forms_render_as_ajax_content_fragments(): void
    {
        [$studyProgram, $course, $academicYear] = $this->createStudentSelection('BCS 101');
        $this->actingAs(User::factory()->create(['role' => 'registrar']));
        $student = Student::create([
            'student_number' => 'VU-2026-001',
            'name' => 'Alex Morgan',
            'email' => 'alex@example.test',
            'programme' => 'Business Computing',
            'year' => 'Year 1',
            'status' => 'Active',
            'study_program_id' => $studyProgram->id,
            'course_id' => $course->id,
            'academic_year_id' => $academicYear->id,
        ]);

        $this->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->get(route('students.create'))
            ->assertOk()
            ->assertHeader('X-Page-Title', 'Add student')
            ->assertSee('Student ID')
            ->assertDontSee('id="appSidebar"', false);

        $this->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->get(route('students.edit', $student))
            ->assertOk()
            ->assertHeader('X-Page-Title', 'Edit student')
            ->assertSee('Alex Morgan')
            ->assertSee('alex@example.test');
    }

    public function test_course_changes_preserve_the_students_enrollment_history(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'registrar']));
        [$studyProgram, $firstCourse, $firstYear] = $this->createStudentSelection('BCS 101');
        [, $secondCourse, $secondYear] = $this->createStudentSelection('BCS 201', $studyProgram);
        $studentDetails = [
            'student_number' => 'VU-2026-002',
            'name' => 'Jamie Smith',
            'email' => 'jamie@example.test',
            'status' => 'Active',
            'study_program_id' => $studyProgram->id,
            'course_id' => $firstCourse->id,
            'academic_year_id' => $firstYear->id,
        ];

        $this->post(route('students.store'), $studentDetails)
            ->assertRedirect(route('students.index'));

        $student = Student::query()->where('student_number', 'VU-2026-002')->firstOrFail();
        $initialEnrollment = Enrollment::query()
            ->where('student_id', $student->id)
            ->firstOrFail();

        $this->assertSame($firstCourse->id, $student->course_id);
        $this->assertSame('active', $initialEnrollment->status);

        $this->put(route('students.update', $student), [
            ...$studentDetails,
            'course_id' => $secondCourse->id,
            'academic_year_id' => $secondYear->id,
        ])->assertRedirect(route('students.index'));

        $this->assertDatabaseHas('enrollments', [
            'id' => $initialEnrollment->id,
            'status' => 'transferred',
        ]);
        $this->assertSame(
            today()->toDateString(),
            Enrollment::query()->findOrFail($initialEnrollment->id)->ended_on->toDateString(),
        );
        $this->assertDatabaseHas('enrollments', [
            'student_id' => $student->id,
            'course_id' => $secondCourse->id,
            'status' => 'active',
            'ended_on' => null,
        ]);
    }

    /**
     * @return array{StudyProgram, Course, AcademicYear}
     */
    private function createStudentSelection(
        string $code,
        ?StudyProgram $studyProgram = null,
        string $programName = 'Day',
    ): array
    {
        $studyProgram ??= StudyProgram::query()->firstOrCreate(['name' => $programName]);
        $course = Course::create([
            'code' => $code,
            'title' => 'Computing '.$code,
            'department' => 'Computing',
            'instructor' => 'Dr. Morgan',
            'credits' => 4,
            'status' => 'In progress',
        ]);
        $course->studyPrograms()->attach($studyProgram);
        $academicYear = AcademicYear::create([
            'course_id' => $course->id,
            'name' => 'Year 1',
            'year_level' => 1,
        ]);

        return [$studyProgram, $course, $academicYear];
    }
}
