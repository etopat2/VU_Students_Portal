<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Course;
use App\Models\Faculty;
use App\Models\Grade;
use App\Models\Lecturer;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request): View|Response
    {
        $user = $request->user();
        $student = $user->isStudent() ? $user->student : null;
        $recentAuditLogs = collect();
        $dashboardCharts = [];

        if ($user->isAdmin()) {
            $stats = [
                'users' => User::count(),
                'roles' => Role::count(),
                'faculties' => Faculty::count(),
                'auditLogs' => AuditLog::count(),
                'students' => Student::count(),
                'courses' => Course::count(),
                'lecturers' => Lecturer::count(),
            ];
            $chartData = collect();
            $dashboardCharts = [
                'userRoles' => User::query()
                    ->select('role as label', DB::raw('COUNT(*) as total'))
                    ->groupBy('role')
                    ->orderBy('role')
                    ->get(),
                'studentsByProgramme' => Student::query()
                    ->selectRaw("COALESCE(NULLIF(programme, ''), 'Unassigned') as label, COUNT(*) as total")
                    ->groupBy('programme')
                    ->orderByDesc('total')
                    ->limit(8)
                    ->get(),
            ];
            $recentGrades = collect();
            $recentAuditLogs = AuditLog::query()
                ->with('user:id,name')
                ->latest('id')
                ->limit(5)
                ->get();
        } else {
            $student?->load([
                'studySession:id,name,audience',
                'studyProgram:id,name',
                'courses:id,code,title,department,credits,status',
                'grades.course:id,code,title',
                'grades.student:id,name,student_number',
            ]);
            $grades = $student?->grades ?? collect();
            $stats = [
                'courses' => $student?->courses->count() ?? 0,
                'grades' => $grades->count(),
                'averageScore' => round((float) $grades->avg('score'), 1),
            ];
            $chartData = $grades
                ->groupBy('letter_grade')
                ->map(fn ($items, $letter) => (object) ['letter_grade' => $letter, 'total' => $items->count()])
                ->values();
            $dashboardCharts = [
                'courseAverages' => $student
                    ? Grade::query()
                        ->join('courses', 'courses.id', '=', 'grades.course_id')
                        ->where('grades.student_id', $student->getKey())
                        ->select('courses.code as label', DB::raw('ROUND(AVG(grades.score), 1) as total'))
                        ->groupBy('courses.id', 'courses.code')
                        ->orderBy('courses.code')
                        ->get()
                    : collect(),
            ];
            $recentGrades = $grades->sortByDesc('created_at')->take(5);
        }

        return $this->renderPage($request, 'students.dashboard', 'Dashboard', [
            'student' => $student,
            'stats' => $stats,
            'chartLabels' => $chartData->pluck('letter_grade')->values(),
            'chartValues' => $chartData->pluck('total')->values(),
            'recentGrades' => $recentGrades,
            'recentAuditLogs' => $recentAuditLogs,
            'metricsUrl' => route('dashboard.metrics'),
            'dashboardCharts' => $dashboardCharts,
        ]);
    }

    public function metrics(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            return response()->json([
                'updatedAt' => now()->toIso8601String(),
                'stats' => [
                    'users' => User::count(),
                    'roles' => Role::count(),
                    'faculties' => Faculty::count(),
                    'auditLogs' => AuditLog::count(),
                    'students' => Student::count(),
                    'courses' => Course::count(),
                    'lecturers' => Lecturer::count(),
                ],
                'charts' => [
                    'userRoles' => User::query()
                        ->select('role as label', DB::raw('COUNT(*) as total'))
                        ->groupBy('role')
                        ->orderBy('role')
                        ->get(),
                    'studentsByProgramme' => Student::query()
                        ->selectRaw("COALESCE(NULLIF(programme, ''), 'Unassigned') as label, COUNT(*) as total")
                        ->groupBy('programme')
                        ->orderByDesc('total')
                        ->limit(8)
                        ->get(),
                ],
                'recentAuditLogs' => AuditLog::query()
                    ->with('user:id,name')
                    ->latest('id')
                    ->limit(5)
                    ->get()
                    ->map(fn (AuditLog $log): array => [
                        'action' => $log->action,
                        'user' => $log->user?->name ?? 'System',
                        'createdAt' => $log->created_at?->toIso8601String(),
                    ]),
            ]);
        }

        $student = $user->student;
        $grades = $student?->grades()->with('course:id,code,title')->latest()->get() ?? collect();

        return response()->json([
            'updatedAt' => now()->toIso8601String(),
            'stats' => [
                'courses' => $student?->courses()->count() ?? 0,
                'grades' => $grades->count(),
                'averageScore' => round((float) $grades->avg('score'), 1),
            ],
            'charts' => [
                'gradeDistribution' => $grades
                    ->groupBy('letter_grade')
                    ->map(fn ($items, $letter): array => ['label' => $letter, 'total' => $items->count()])
                    ->values(),
                'courseAverages' => $student
                    ? Grade::query()
                        ->join('courses', 'courses.id', '=', 'grades.course_id')
                        ->where('grades.student_id', $student->getKey())
                        ->select('courses.code as label', DB::raw('ROUND(AVG(grades.score), 1) as total'))
                        ->groupBy('courses.id', 'courses.code')
                        ->orderBy('courses.code')
                        ->get()
                    : collect(),
            ],
            'recentGrades' => $grades->take(5)->map(fn (Grade $grade): array => [
                'courseCode' => $grade->course?->code ?? 'Course',
                'courseTitle' => $grade->course?->title ?? '',
                'letterGrade' => $grade->letter_grade,
                'createdAt' => $grade->created_at?->toIso8601String(),
            ]),
        ]);
    }

    public function courses(Request $request): View|Response
    {
        $student = $request->user()->student;
        $courses = $student
            ? $student->courses()->orderBy('code')->get()
            : collect();

        return $this->renderPage($request, 'students.courses', 'My courses', compact('courses'));
    }

    public function grades(Request $request): View|Response
    {
        $student = $request->user()->student;
        $grades = $student
            ? $student->grades()
                ->with(['student:id,name,student_number', 'course:id,code,title'])
                ->orderByDesc('term')
                ->orderBy('course_id')
                ->get()
            : collect();

        return $this->renderPage($request, 'students.grades', 'My grades', compact('grades'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function renderPage(Request $request, string $view, string $title, array $data = []): View|Response
    {
        $page = view($view, $data);

        if ($request->ajax()) {
            return response($page->renderSections()['content'])
                ->header('X-Page-Title', $title);
        }

        return $page;
    }
}
