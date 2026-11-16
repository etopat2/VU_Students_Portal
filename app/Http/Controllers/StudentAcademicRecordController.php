<?php

namespace App\Http\Controllers;

use App\Models\Session;
use App\Models\Student;
use Illuminate\Http\JsonResponse;

class StudentAcademicRecordController extends Controller
{
    public function examPermit(Student $student, Session $session): JsonResponse
    {
        $this->authorize('viewExamPermit', [$student, $session]);
        abort_unless($student->isFinanciallyCleared(), 403, 'Financial clearance is required.');

        $registration = $student->semesterRegistrations()
            ->with(['session.academicYear.course', 'moduleRegistrations.module'])
            ->where('session_id', $session->id)
            ->firstOrFail();

        return response()->json([
            'student' => [
                'student_number' => $student->student_number,
                'name' => $student->name,
            ],
            'session' => $registration->session->name,
            'course' => $registration->session->academicYear->course->title,
            'modules' => $registration->moduleRegistrations->map(fn ($moduleRegistration): array => [
                'code' => $moduleRegistration->module->code,
                'title' => $moduleRegistration->module->title,
            ]),
            'financial_clearance' => 'cleared',
        ]);
    }

    public function results(Student $student, Session $session): JsonResponse
    {
        $this->authorize('viewResults', $student);
        abort_unless($student->isFinanciallyCleared(), 403, 'Financial clearance is required.');

        $registration = $student->semesterRegistrations()
            ->with(['session', 'moduleRegistrations.module', 'moduleRegistrations.finalResult'])
            ->where('session_id', $session->id)
            ->firstOrFail();

        return response()->json([
            'session' => $registration->session->name,
            'semester_gpa' => $registration->semesterGpa(),
            'results' => $registration->moduleRegistrations
                ->filter(fn ($moduleRegistration): bool => $moduleRegistration->finalResult !== null)
                ->map(fn ($moduleRegistration): array => [
                    'module_code' => $moduleRegistration->module->code,
                    'module_title' => $moduleRegistration->module->title,
                    'final_mark' => $moduleRegistration->finalResult->final_mark,
                    'letter_grade' => $moduleRegistration->finalResult->letter_grade,
                    'grade_point' => $moduleRegistration->finalResult->grade_point,
                ])
                ->values(),
        ]);
    }
}
