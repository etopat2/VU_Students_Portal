<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Institution;
use App\Models\Lecturer;
use App\Models\Module;
use App\Models\StudyProgram;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CoursesControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_course_can_be_created_with_valid_details(): void
    {
        $options = $this->createCourseOptions();
        $this->actingAs(User::factory()->create(['role' => 'registrar']));
        $response = $this->post(route('courses.store'), [
            ...$options,
            'code' => 'BCS 210',
            'title' => 'Database Systems',
            'credits' => 4,
            'schedule' => 'Mon, Wed · 10:00 AM',
            'status' => 'In progress',
        ]);

        $response->assertRedirect(route('courses.index'));
        $response->assertSessionHas('laravel-toaster-magic::messages', fn (array $messages): bool => $messages[0]['type'] === 'success'
            && $messages[0]['message'] === 'Course added successfully.');
        $this->assertDatabaseHas('courses', [
            'code' => 'BCS 210',
            'title' => 'Database Systems',
            'credits' => 4,
        ]);
        $this->assertDatabaseHas('course_study_program', [
            'course_id' => Course::query()->where('code', 'BCS 210')->value('id'),
            'study_program_id' => $options['study_program_ids'][0],
        ]);
    }

    public function test_course_updates_and_deletes_through_resource_routes(): void
    {
        $options = $this->createCourseOptions();
        $this->actingAs(User::factory()->create(['role' => 'registrar']));
        $course = Course::create([
            'code' => 'BCS 210',
            'title' => 'Database Systems',
            'department' => 'Computing',
            'instructor' => 'Dr. Morgan',
            'credits' => 4,
            'status' => 'In progress',
        ]);

        $this->put(route('courses.update', $course), [
            ...$options,
            'code' => 'BCS 210',
            'title' => 'Advanced Database Systems',
            'credits' => 5,
            'schedule' => 'Tue, Thu · 10:00 AM',
            'status' => 'In progress',
        ])->assertRedirect(route('courses.index'));

        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'title' => 'Advanced Database Systems',
            'credits' => 5,
        ]);

        $this->delete(route('courses.destroy', $course))
            ->assertRedirect(route('courses.index'));

        $this->assertDatabaseMissing('courses', ['id' => $course->id]);
    }

    public function test_course_creation_rejects_invalid_credit_units(): void
    {
        $options = $this->createCourseOptions();
        $this->actingAs(User::factory()->create(['role' => 'registrar']));
        $this->from(route('courses.create'))
            ->post(route('courses.store'), [
                ...$options,
                'code' => 'BCS 210',
                'title' => 'Database Systems',
                'credits' => 0,
                'schedule' => null,
                'status' => 'In progress',
            ])
            ->assertRedirect(route('courses.create'))
            ->assertSessionHasErrors('credits');

        $this->assertDatabaseCount('courses', 0);
    }

    public function test_create_and_edit_forms_render_as_ajax_content_fragments(): void
    {
        $options = $this->createCourseOptions();
        $this->actingAs(User::factory()->create(['role' => 'registrar']));
        $course = Course::create([
            'code' => 'BCS 210',
            'title' => 'Database Systems',
            'department' => 'Computing',
            'instructor' => 'Dr. Morgan',
            'credits' => 4,
            'status' => 'In progress',
        ]);

        $this->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->get(route('courses.create'))
            ->assertOk()
            ->assertHeader('X-Page-Title', 'Add course')
            ->assertSee('Course code')
            ->assertDontSee('id="appSidebar"', false);

        $this->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->get(route('courses.edit', $course))
            ->assertOk()
            ->assertHeader('X-Page-Title', 'Edit course')
            ->assertSee('Database Systems')
            ->assertSee('BCS 210');
    }

    public function test_course_rejects_a_department_that_does_not_belong_to_the_selected_faculty(): void
    {
        $options = $this->createCourseOptions();
        $otherFaculty = Faculty::create([
            'institution_id' => Institution::query()->value('id'),
            'name' => 'Business',
            'code' => 'BUS',
        ]);
        $this->actingAs(User::factory()->create(['role' => 'registrar']));

        $this->from(route('courses.create'))
            ->post(route('courses.store'), [
                ...$options,
                'faculty_id' => $otherFaculty->id,
                'code' => 'BCS 220',
                'title' => 'Web Development',
                'credits' => 4,
                'status' => 'In progress',
            ])
            ->assertRedirect(route('courses.create'))
            ->assertSessionHasErrors([
                'department_id' => 'Choose a department in the selected faculty.',
            ]);

        $this->assertDatabaseMissing('courses', ['code' => 'BCS 220']);
    }

    public function test_courses_from_different_faculties_can_share_a_module(): void
    {
        $options = $this->createCourseOptions();
        $otherInstitution = Institution::create(['name' => 'Regional Institute', 'code' => 'RI']);
        $otherFaculty = Faculty::create([
            'institution_id' => $otherInstitution->id,
            'name' => 'Education',
            'code' => 'EDU',
        ]);
        $otherDepartment = Department::create([
            'faculty_id' => $otherFaculty->id,
            'name' => 'Education Studies',
            'code' => 'EDS',
        ]);
        $sharedModule = Module::create([
            'department_id' => $otherDepartment->id,
            'code' => 'ED101',
            'title' => 'Foundations of Education',
            'credit_units' => 4,
            'year_level' => 1,
            'session_number' => 1,
        ]);
        $this->actingAs(User::factory()->create(['role' => 'registrar']));

        $this->post(route('courses.store'), [
            ...$options,
            'code' => 'BCS 301',
            'title' => 'Information Systems',
            'credits' => 4,
            'status' => 'In progress',
            'module_ids' => [$sharedModule->id],
        ])->assertRedirect(route('courses.index'));
        $firstCourse = Course::query()->where('code', 'BCS 301')->firstOrFail();

        $otherCourseOptions = $this->createCourseOptions('Other');
        $secondCourse = Course::create([
            'code' => 'BBA 301',
            'title' => 'Business Administration',
            'department' => 'Business',
            'instructor' => 'Dr. Morgan',
            'credits' => 4,
            'status' => 'In progress',
        ]);
        $this->put(route('courses.update', $secondCourse), [
            ...$otherCourseOptions,
            'code' => 'BBA 301',
            'title' => 'Business Administration',
            'credits' => 4,
            'status' => 'In progress',
            'module_ids' => [$sharedModule->id],
        ])->assertRedirect(route('courses.index'));

        $this->assertDatabaseHas('course_module', [
            'course_id' => $firstCourse->id,
            'module_id' => $sharedModule->id,
        ]);
        $this->assertDatabaseHas('course_module', [
            'course_id' => $secondCourse->id,
            'module_id' => $sharedModule->id,
        ]);
        $this->assertSame(2, $sharedModule->courses()->count());
    }

    public function test_admin_cannot_manage_course_catalogue_records(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('courses.index'))
            ->assertForbidden();
    }

    /**
     * @return array{faculty_id: int, department_id: int, lecturer_id: int, study_program_ids: list<int>}
     */
    private function createCourseOptions(string $suffix = ''): array
    {
        $institution = Institution::create([
            'name' => 'Example University'.$suffix,
            'code' => 'EXU'.strtoupper($suffix),
        ]);
        $faculty = Faculty::create([
            'institution_id' => $institution->id,
            'name' => 'Science'.$suffix,
            'code' => 'SCI'.strtoupper($suffix),
        ]);
        $department = Department::create([
            'faculty_id' => $faculty->id,
            'name' => 'Computing'.$suffix,
            'code' => 'COMP'.strtoupper($suffix),
        ]);
        $lecturer = Lecturer::create([
            'lecturer_number' => 'LEC-001'.$suffix,
            'name' => 'Dr. Morgan',
            'email' => 'lecturer'.strtolower($suffix).'@example.test',
            'department' => $department->name,
            'department_id' => $department->id,
            'qualification' => 'MSc',
            'status' => 'Active',
        ]);
        $studyProgram = StudyProgram::create(['name' => 'Day'.$suffix]);

        return [
            'faculty_id' => $faculty->id,
            'department_id' => $department->id,
            'lecturer_id' => $lecturer->id,
            'study_program_ids' => [$studyProgram->id],
        ];
    }
}
