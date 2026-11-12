<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $admin = config('portal.demo_accounts.admin');
        $studentAccount = config('portal.demo_accounts.student');

        foreach ([$admin, $studentAccount] as $account) {
            if (! is_string($account['password']) || trim($account['password']) === '') {
                throw new RuntimeException(
                    'Set PORTAL_ADMIN_PASSWORD and PORTAL_STUDENT_PASSWORD in your local .env before seeding demo accounts.',
                );
            }
        }

        User::updateOrCreate(
            ['email' => $admin['email']],
            [
                'name' => $admin['name'],
                'password' => $admin['password'],
                'role' => 'admin',
            ],
        );

        $existingStudent = Student::query()
            ->where('email', $studentAccount['email'])
            ->orWhere('student_number', 'VU-BBC-2411-1019-Day')
            ->first();

        $studentUser = User::updateOrCreate(
            ['email' => $studentAccount['email']],
            [
                'name' => $existingStudent?->name ?? $studentAccount['name'],
                'password' => $studentAccount['password'],
                'role' => 'student',
            ],
        );

        $student = $existingStudent ?? Student::firstOrCreate(
            ['email' => $studentAccount['email']],
            [
                'student_number' => 'VU-BBC-2411-1019-Day',
                'name' => $studentUser->name,
                'programme' => 'BSc. Business Computing and Data Analytics',
                'year' => 'Year 2',
                'status' => 'Active',
            ],
        );

        if ($student->user_id !== $studentUser->id) {
            $student->user()->associate($studentUser)->save();
        }

        if (! $student->courses()->exists()) {
            $student->courses()->syncWithoutDetaching(Course::query()->pluck('courses.id'));
        }
    }
}
