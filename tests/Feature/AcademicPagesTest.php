<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Grade;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcademicPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_academic_pages_render_database_records_in_the_shared_layout(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $this->createAcademicRecords();

        $pages = [
            ['students.index', 'Students', 'Student directory', 'Alex Morgan'],
            ['courses.index', 'Courses', 'Course catalogue', 'Database Systems'],
            ['grades.index', 'Grades', 'Assessment results', 'Database Systems'],
        ];

        foreach ($pages as [$route, $title, $heading, $record]) {
            $response = $this->get(route($route));

            $response->assertOk();
            $response->assertSee("<title>{$title} | VU Student Portal</title>", false);
            $response->assertSee('id="appSidebar"', false);
            $response->assertSee('id="pageContent"', false);
            $response->assertSee($heading);
            $response->assertSee($record);
        }
    }

    public function test_ajax_navigation_returns_academic_page_fragments_and_titles(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $this->createAcademicRecords();

        $pages = [
            ['students.index', 'Students', 'Student directory'],
            ['courses.index', 'Courses', 'Course catalogue'],
            ['grades.index', 'Grades', 'Assessment results'],
        ];

        foreach ($pages as [$route, $title, $heading]) {
            $response = $this->withHeaders([
                'Accept' => 'text/html',
                'X-Requested-With' => 'XMLHttpRequest',
            ])->get(route($route));

            $response->assertOk();
            $response->assertHeader('X-Page-Title', $title);
            $response->assertSee($heading);
            $response->assertDontSee('Student services, made simple.');
        }
    }

    public function test_academic_summary_cards_reflect_database_records(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $this->createAcademicRecords();

        $this->get(route('students.index'))
            ->assertOk()
            ->assertSee('Students listed')
            ->assertSee('1')
            ->assertSee('Active students');

        $this->get(route('courses.index'))
            ->assertOk()
            ->assertSee('Credit units')
            ->assertSee('4');

        $this->get(route('grades.index'))
            ->assertOk()
            ->assertSee('Average score')
            ->assertSee('88.0')
            ->assertSee('Highest score');
    }

    private function createAcademicRecords(): void
    {
        $student = Student::create([
            'student_number' => 'VU-2024-001',
            'name' => 'Alex Morgan',
            'email' => 'alex@example.test',
            'programme' => 'Business Computing',
            'year' => 'Year 2',
            'status' => 'Active',
        ]);

        $course = Course::create([
            'code' => 'BCS 210',
            'title' => 'Database Systems',
            'department' => 'Computing',
            'instructor' => 'Dr. Morgan',
            'credits' => 4,
            'schedule' => 'Mon, Wed · 10:00 AM',
            'status' => 'In progress',
        ]);

        Grade::create([
            'student_id' => $student->id,
            'course_id' => $course->id,
            'assessment' => 'Coursework',
            'score' => 88,
            'letter_grade' => 'A',
            'term' => 'Semester 1 · 2026',
        ]);
    }
}
