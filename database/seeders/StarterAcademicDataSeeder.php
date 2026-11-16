<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Course;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Institution;
use App\Models\Lecturer;
use App\Models\Module;
use App\Models\StudyProgram;
use Illuminate\Database\Seeder;

class StarterAcademicDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(StudySessionSeeder::class);

        $institution = Institution::query()->firstOrCreate(
            ['code' => 'VU'],
            ['name' => 'Victoria University'],
        );

        $faculties = [
            'FST' => 'Faculty of Science & Technology',
            'FBM' => 'Faculty of Business & Management',
            'FEH' => 'Faculty of Education & Humanities',
        ];

        foreach ($faculties as $code => $name) {
            Faculty::query()->firstOrCreate(
                ['code' => $code],
                ['institution_id' => $institution->id, 'name' => $name],
            );
        }

        $facultyByCode = Faculty::query()->whereIn('code', array_keys($faculties))->get()->keyBy('code');

        $departments = [
            'CSIT' => ['faculty' => 'FST', 'name' => 'Computer Science & IT'],
            'AF' => ['faculty' => 'FBM', 'name' => 'Accounting & Finance'],
            'FOE' => ['faculty' => 'FEH', 'name' => 'Foundations of Education'],
        ];

        foreach ($departments as $code => $details) {
            Department::query()->firstOrCreate(
                ['code' => $code],
                [
                    'faculty_id' => $facultyByCode[$details['faculty']]->id,
                    'name' => $details['name'],
                ],
            );
        }

        $departmentByCode = Department::query()->whereIn('code', array_keys($departments))->get()->keyBy('code');

        $programNames = ['Day', 'Evening', 'Weekend', 'Holiday'];
        $studyPrograms = collect($programNames)->mapWithKeys(
            fn (string $name): array => [$name => StudyProgram::query()->firstOrCreate(['name' => $name])],
        );

        $instructors = [
            'CSIT' => ['LEC-SEED-CS', 'Dr. Sarah Nambasa', 'sarah.nambasa@vu.ac.ug'],
            'AF' => ['LEC-SEED-AF', 'Mr. Daniel Okello', 'daniel.okello@vu.ac.ug'],
            'FOE' => ['LEC-SEED-ED', 'Dr. Grace Achieng', 'grace.achieng@vu.ac.ug'],
        ];

        foreach ($instructors as $departmentCode => [$staffNumber, $name, $email]) {
            Lecturer::query()->firstOrCreate(
                ['lecturer_number' => $staffNumber],
                [
                    'name' => $name,
                    'email' => $email,
                    'department' => $departmentByCode[$departmentCode]->name,
                    'department_id' => $departmentByCode[$departmentCode]->id,
                    'qualification' => 'Master of Science',
                    'status' => 'Active',
                ],
            );
        }

        $lecturerByDepartment = Lecturer::query()
            ->whereIn('lecturer_number', collect($instructors)->pluck(0))
            ->get()
            ->keyBy('department_id');

        $courses = [
            [
                'code' => 'BSIT',
                'title' => 'BSc. Information Technology',
                'department' => 'CSIT',
                'programs' => ['Day', 'Evening'],
                'modules' => [
                    ['IT101', 'Computer Fundamentals', 4, 1, 1],
                    ['IT102', 'Systems Analysis and Design', 4, 1, 2],
                    ['IT201', 'Database Systems', 4, 2, 1],
                    ['IT302', 'Web Application Development', 4, 3, 1],
                    ['IT401', 'Information Security', 4, 4, 2],
                ],
            ],
            [
                'code' => 'BBA',
                'title' => 'Bachelor of Business Administration',
                'department' => 'AF',
                'programs' => ['Evening', 'Weekend'],
                'modules' => [
                    ['BA101', 'Principles of Management', 4, 1, 1],
                    ['BA102', 'Financial Accounting I', 4, 1, 2],
                    ['BA201', 'Business Statistics', 4, 2, 1],
                    ['BA302', 'Human Resource Management', 4, 3, 1],
                    ['BA401', 'Strategic Management', 4, 4, 2],
                ],
            ],
            [
                'code' => 'DPEI',
                'title' => 'Diploma in Primary Education (In-service)',
                'department' => 'FOE',
                'programs' => ['Evening', 'Weekend', 'Holiday'],
                'modules' => [
                    ['ED101', 'Foundations of Education', 4, 1, 1],
                    ['ED102', 'Educational Psychology', 4, 1, 2],
                    ['ED201', 'Teaching Methods', 4, 2, 1],
                    ['ED202', 'Curriculum Studies', 4, 2, 2],
                    ['ED301', 'Classroom Assessment', 4, 3, 1],
                ],
            ],
        ];

        foreach ($courses as $courseData) {
            $department = $departmentByCode[$courseData['department']];
            $lecturer = $lecturerByDepartment[$department->id];
            $course = Course::query()->firstOrCreate(
                ['code' => $courseData['code']],
                [
                    'title' => $courseData['title'],
                    'department_id' => $department->id,
                    'lecturer_id' => $lecturer->id,
                    'department' => $department->name,
                    'instructor' => $lecturer->name,
                    'credits' => 120,
                    'status' => 'In progress',
                ],
            );
            $course->studyPrograms()->syncWithoutDetaching(
                collect($courseData['programs'])->map(fn (string $name) => $studyPrograms[$name]->id)->all(),
            );

            foreach (range(1, 4) as $yearLevel) {
                AcademicYear::query()->firstOrCreate(
                    ['course_id' => $course->id, 'year_level' => $yearLevel],
                    ['name' => 'Year '.$yearLevel],
                );
            }

            foreach ($courseData['modules'] as [$code, $title, $credits, $yearLevel, $sessionNumber]) {
                $module = Module::query()->firstOrCreate(
                    ['code' => $code],
                    [
                        'department_id' => $department->id,
                        'title' => $title,
                        'credit_units' => $credits,
                        'year_level' => $yearLevel,
                        'session_number' => $sessionNumber,
                    ],
                );
                $course->modules()->syncWithoutDetaching([$module->id]);
            }
        }
    }
}
