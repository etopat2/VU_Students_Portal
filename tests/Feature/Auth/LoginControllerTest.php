<?php

namespace Tests\Feature\Auth;

use App\Models\Course;
use App\Models\Grade;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_and_student_accounts_can_sign_in_and_sign_out(): void
    {
        foreach (['admin', 'student'] as $role) {
            $user = User::factory()->create([
                'email' => "{$role}@example.test",
                'password' => 'demo-password',
                'role' => $role,
            ]);

            $this->post(route('login.store'), [
                'email' => strtoupper($user->email),
                'password' => 'demo-password',
            ])->assertRedirect(route('dashboard'));

            $this->assertAuthenticatedAs($user);

            $this->post(route('logout'))
                ->assertRedirect(route('login'));

            $this->assertGuest();
        }
    }

    public function test_invalid_credentials_do_not_authenticate_the_user(): void
    {
        User::factory()->admin()->create([
            'email' => 'admin@example.test',
            'password' => 'correct-password',
        ]);

        $this->from(route('login'))
            ->post(route('login.store'), [
                'email' => 'admin@example.test',
                'password' => 'wrong-password',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['email' => 'The email or password is incorrect.']);

        $this->assertGuest();
    }

    public function test_sign_in_attempts_are_limited_per_email_and_ip(): void
    {
        User::factory()->admin()->create([
            'email' => 'admin@example.test',
            'password' => 'correct-password',
        ]);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->from(route('login'))
                ->post(route('login.store'), [
                    'email' => 'admin@example.test',
                    'password' => 'wrong-password',
                ])
                ->assertRedirect(route('login'));
        }

        $this->from(route('login'))
            ->post(route('login.store'), [
                'email' => 'admin@example.test',
                'password' => 'correct-password',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_accounts_with_unsupported_roles_cannot_sign_in(): void
    {
        User::factory()->create([
            'email' => 'staff@example.test',
            'password' => 'staff-password',
            'role' => 'staff',
        ]);

        $this->from(route('login'))
            ->post(route('login.store'), [
                'email' => 'staff@example.test',
                'password' => 'staff-password',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['email' => 'This account does not have portal access.']);

        $this->assertGuest();
    }

    public function test_guests_are_redirected_to_sign_in_and_students_cannot_open_admin_pages(): void
    {
        $this->get(route('students.index'))
            ->assertRedirect(route('login'));

        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)
            ->get(route('students.index'))
            ->assertForbidden();
    }

    public function test_student_course_and_grade_pages_only_show_their_own_records(): void
    {
        $user = User::factory()->create(['role' => 'student']);
        $student = $this->createStudent('VU-OWN-001', 'Own Student', 'own@example.test');
        $student->user()->associate($user)->save();

        $otherStudent = $this->createStudent('VU-OTHER-001', 'Other Student', 'other@example.test');
        $course = $this->createCourse('OWN 101', 'My Course');
        $otherCourse = $this->createCourse('OTH 202', 'Private Other Course');
        $student->courses()->attach($course);
        $otherStudent->courses()->attach($otherCourse);

        Grade::create([
            'student_id' => $student->id,
            'course_id' => $course->id,
            'assessment' => 'Coursework',
            'score' => 88,
            'letter_grade' => 'A',
            'term' => 'Semester 1',
        ]);
        Grade::create([
            'student_id' => $otherStudent->id,
            'course_id' => $otherCourse->id,
            'assessment' => 'Private coursework',
            'score' => 42,
            'letter_grade' => 'F',
            'term' => 'Semester 1',
        ]);

        $this->actingAs($user);

        $this->get(route('student.courses'))
            ->assertOk()
            ->assertSee('My Course')
            ->assertDontSee('Private Other Course');

        $this->get(route('student.grades'))
            ->assertOk()
            ->assertSee('My Course')
            ->assertDontSee('Private Other Course')
            ->assertDontSee('Private coursework');
    }

    public function test_admin_is_forbidden_from_student_only_pages(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('student.courses'))
            ->assertForbidden();
    }

    private function createStudent(string $number, string $name, string $email): Student
    {
        return Student::create([
            'student_number' => $number,
            'name' => $name,
            'email' => $email,
            'programme' => 'Business Computing',
            'year' => 'Year 1',
            'status' => 'Active',
        ]);
    }

    private function createCourse(string $code, string $title): Course
    {
        return Course::create([
            'code' => $code,
            'title' => $title,
            'department' => 'Computing',
            'instructor' => 'Dr. Morgan',
            'credits' => 4,
            'status' => 'In progress',
        ]);
    }
}
