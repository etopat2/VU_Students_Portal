<?php

namespace Tests\Feature;

use App\Models\Accountant;
use App\Models\Lecturer;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\GenerateMissingUserAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class GenerateMissingUserAccountsSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_missing_student_lecturer_and_accountant_accounts_are_created_idempotently(): void
    {
        Student::create([
            'student_number' => 'VU-2026-001',
            'name' => 'Alex Student',
            'email' => 'alex.student@example.test',
            'programme' => 'Information Technology',
            'year' => 'Year 1',
            'status' => 'Active',
        ]);
        Lecturer::create([
            'lecturer_number' => 'VU-LEC-001',
            'name' => 'Jamie Lecturer',
            'email' => 'jamie.lecturer@example.test',
            'department' => 'Computing',
            'qualification' => 'MSc',
            'status' => 'Active',
        ]);
        Accountant::create([
            'staff_number' => 'VU-ACC-001',
            'name' => 'Taylor Accountant',
            'email' => 'taylor.accountant@example.test',
            'status' => 'Active',
        ]);

        $this->seed(GenerateMissingUserAccountsSeeder::class);
        $this->seed(GenerateMissingUserAccountsSeeder::class);

        $this->assertDatabaseCount('users', 3);

        foreach ([
            ['alex.student@example.test', 'student'],
            ['jamie.lecturer@example.test', 'lecturer'],
            ['taylor.accountant@example.test', 'accountant'],
        ] as [$email, $role]) {
            $user = User::query()->where('email', $email)->firstOrFail();

            $this->assertTrue(Hash::check('Password123', $user->password));
            $this->assertTrue($user->must_change_password);
            $this->assertTrue($user->hasRole($role));
        }
    }
}
