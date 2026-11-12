<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_accounts_are_seeded_and_existing_student_courses_are_preserved(): void
    {
        config([
            'portal.demo_accounts.admin' => [
                'name' => 'Demo Administrator',
                'email' => 'admin@example.test',
                'password' => 'admin-demo-secret',
            ],
            'portal.demo_accounts.student' => [
                'name' => 'Demo Student',
                'email' => 'student@example.test',
                'password' => 'student-demo-secret',
            ],
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
        $student = Student::create([
            'student_number' => 'VU-BBC-2411-1019-Day',
            'name' => 'Existing Student Name',
            'email' => 'student@example.test',
            'programme' => 'Existing Programme',
            'year' => 'Year 2',
            'status' => 'Active',
        ]);

        $this->seed(DatabaseSeeder::class);

        $admin = User::where('email', 'admin@example.test')->firstOrFail();
        $studentUser = User::where('email', 'student@example.test')->firstOrFail();

        $this->assertSame('admin', $admin->role);
        $this->assertSame('student', $studentUser->role);
        $this->assertTrue(Hash::check('admin-demo-secret', $admin->password));
        $this->assertTrue(Hash::check('student-demo-secret', $studentUser->password));
        $this->assertSame($studentUser->id, $student->fresh()->user_id);
        $this->assertTrue($student->fresh()->courses()->whereKey($course->id)->exists());
        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'title' => 'Database Systems',
            'schedule' => 'Mon, Wed · 10:00 AM',
        ]);
        $this->assertDatabaseHas('students', [
            'id' => $student->id,
            'name' => 'Existing Student Name',
            'programme' => 'Existing Programme',
        ]);

        $this->post(route('login.store'), [
            'email' => 'admin@example.test',
            'password' => 'admin-demo-secret',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($admin);
    }
}
