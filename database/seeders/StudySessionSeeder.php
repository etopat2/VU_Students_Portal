<?php

namespace Database\Seeders;

use App\Models\StudySession;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class StudySessionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sessions = [
            ['DAY', 'Day', 'all_students', 'Weekday daytime study schedule.'],
            ['WEEKEND', 'Weekend', 'all_students', 'Weekend study schedule.'],
            ['EVENING', 'Evening', 'all_students', 'Evening study schedule for learners balancing work and study.'],
            ['HOL-IN-SERVICE', 'Holiday (In-service Teachers)', 'in_service_teachers', 'Holiday study program designed for in-service teachers.'],
        ];

        foreach ($sessions as [$code, $name, $audience, $description]) {
            StudySession::query()->firstOrCreate(
                ['code' => $code],
                ['name' => $name, 'audience' => $audience, 'description' => $description, 'is_active' => true],
            );
        }
    }
}
