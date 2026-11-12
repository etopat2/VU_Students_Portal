<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Grade;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GradesControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_grade_can_be_created_with_valid_academic_records(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        [$student, $course] = $this->createAcademicRecords();

        $this->post(route('grades.store'), [
            'student_id' => $student->id,
            'course_id' => $course->id,
            'assessment' => 'Coursework',
            'score' => 88.5,
            'letter_grade' => 'A',
            'term' => 'Semester 1 · 2026',
        ])->assertRedirect(route('grades.index'));

        $this->assertDatabaseHas('grades', [
            'student_id' => $student->id,
            'course_id' => $course->id,
            'assessment' => 'Coursework',
            'score' => 88.5,
            'letter_grade' => 'A',
        ]);
    }

    public function test_grade_updates_and_deletes_through_resource_routes(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        [$student, $course] = $this->createAcademicRecords();
        $grade = Grade::create([
            'student_id' => $student->id,
            'course_id' => $course->id,
            'assessment' => 'Coursework',
            'score' => 78,
            'letter_grade' => 'B',
            'term' => 'Semester 1 · 2026',
        ]);

        $this->put(route('grades.update', $grade), [
            'student_id' => $student->id,
            'course_id' => $course->id,
            'assessment' => 'Coursework',
            'score' => 91,
            'letter_grade' => 'A',
            'term' => 'Semester 1 · 2026',
        ])->assertRedirect(route('grades.index'));

        $this->assertDatabaseHas('grades', [
            'id' => $grade->id,
            'score' => 91,
            'letter_grade' => 'A',
        ]);

        $this->delete(route('grades.destroy', $grade))
            ->assertRedirect(route('grades.index'));

        $this->assertDatabaseMissing('grades', ['id' => $grade->id]);
    }

    public function test_grade_rejects_scores_outside_the_valid_range(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        [$student, $course] = $this->createAcademicRecords();

        $this->from(route('grades.create'))
            ->post(route('grades.store'), [
                'student_id' => $student->id,
                'course_id' => $course->id,
                'assessment' => 'Coursework',
                'score' => 101,
                'letter_grade' => 'A',
                'term' => 'Semester 1 · 2026',
            ])
            ->assertRedirect(route('grades.create'))
            ->assertSessionHasErrors('score');

        $this->assertDatabaseCount('grades', 0);
    }

    public function test_add_and_edit_forms_render_with_academic_record_options(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        [$student, $course] = $this->createAcademicRecords();
        $grade = Grade::create([
            'student_id' => $student->id,
            'course_id' => $course->id,
            'assessment' => 'Coursework',
            'score' => 88,
            'letter_grade' => 'A',
            'term' => 'Semester 1 · 2026',
        ]);

        $this->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->get(route('grades.create'))
            ->assertOk()
            ->assertHeader('X-Page-Title', 'Add grade')
            ->assertSee('Alex Morgan')
            ->assertSee('Database Systems')
            ->assertDontSee('id="appSidebar"', false);

        $this->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->get(route('grades.edit', $grade))
            ->assertOk()
            ->assertHeader('X-Page-Title', 'Edit grade')
            ->assertSee('Coursework')
            ->assertSee('Semester 1 · 2026');
    }

    private function createAcademicRecords(): array
    {
        $student = Student::create([
            'student_number' => 'VU-2026-001',
            'name' => 'Alex Morgan',
            'email' => 'alex@example.test',
            'programme' => 'Business Computing',
            'year' => 'Year 1',
            'status' => 'Active',
        ]);

        $course = Course::create([
            'code' => 'BCS 210',
            'title' => 'Database Systems',
            'department' => 'Computing',
            'instructor' => 'Dr. Morgan',
            'credits' => 4,
            'status' => 'In progress',
        ]);

        return [$student, $course];
    }
}
