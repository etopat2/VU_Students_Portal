<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Grade;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_ajax_navigation_returns_the_requested_page_fragment(): void
    {
        $this->actingAs(User::factory()->admin()->create([
            'name' => 'Portal Administrator',
        ]));

        $pages = [
            ['dashboard', 'Dashboard', 'Welcome back, Portal Administrator'],
            ['profile', 'Profile', 'Administrator profile'],
            ['settings', 'Settings', 'Portal settings'],
        ];

        foreach ($pages as [$route, $title, $content]) {
            $response = $this->withHeader('X-Requested-With', 'XMLHttpRequest')
                ->get(route($route));

            $response->assertOk();
            $response->assertHeader('X-Page-Title', $title);
            $response->assertSee($content);
            $response->assertDontSee('Academic administration made simple.');
        }
    }

    public function test_profile_and_settings_render_inside_the_shared_layout(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        foreach (['profile', 'settings'] as $route) {
            $response = $this->get(route($route));

            $response->assertOk();
            $response->assertSee('id="appSidebar"', false);
            $response->assertSee('id="pageContent"', false);
            $response->assertSee('Academic administration made simple.');
        }
    }

    public function test_admin_dashboard_uses_live_academic_summary_data(): void
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
        Grade::create([
            'student_id' => $student->id,
            'course_id' => $course->id,
            'assessment' => 'Coursework',
            'score' => 88,
            'letter_grade' => 'A',
            'term' => 'Semester 1',
        ]);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('ADMINISTRATOR OVERVIEW')
            ->assertSee('1 active student')
            ->assertSee('Grade distribution')
            ->assertSee('Recent results')
            ->assertDontSee('Attendance');
    }

    public function test_student_dashboard_uses_only_the_linked_student_summary(): void
    {
        $user = User::factory()->create(['role' => 'student']);
        $student = Student::create([
            'student_number' => 'VU-2026-002',
            'name' => 'Student User',
            'email' => 'student@example.test',
            'programme' => 'Business Computing',
            'year' => 'Year 1',
            'status' => 'Active',
        ]);
        $student->user()->associate($user)->save();

        $course = Course::create([
            'code' => 'BCS 220',
            'title' => 'Web Development',
            'department' => 'Computing',
            'instructor' => 'Dr. Morgan',
            'credits' => 4,
            'status' => 'In progress',
        ]);
        $student->courses()->attach($course);
        Grade::create([
            'student_id' => $student->id,
            'course_id' => $course->id,
            'assessment' => 'Coursework',
            'score' => 84,
            'letter_grade' => 'B+',
            'term' => 'Semester 1',
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('STUDENT OVERVIEW')
            ->assertSee('Enrolled courses')
            ->assertSee('84.0')
            ->assertDontSee('Administration');
    }

    public function test_dashboard_escapes_the_authenticated_users_name(): void
    {
        $user = User::factory()->admin()->create([
            'name' => "<script>alert('xss')</script>",
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('&lt;script&gt;', false)
            ->assertDontSee("<script>alert('xss')</script>", false);
    }
}
