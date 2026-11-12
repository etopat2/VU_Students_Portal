<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Grade;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index(Request $request): View|Response
    {
        $user = $request->user();
        $student = $user->isStudent() ? $user->student : null;

        if ($user->isAdmin()) {
            $stats = [
                'students' => Student::count(),
                'activeStudents' => Student::where('status', 'Active')->count(),
                'courses' => Course::count(),
                'grades' => Grade::count(),
                'averageScore' => round((float) Grade::avg('score'), 1),
            ];
            $chartData = Grade::query()
                ->selectRaw('letter_grade, COUNT(*) as total')
                ->groupBy('letter_grade')
                ->orderBy('letter_grade')
                ->get();
            $recentGrades = Grade::query()
                ->with(['student:id,name,student_number', 'course:id,code,title'])
                ->latest()
                ->limit(5)
                ->get();
        } else {
            $student?->load([
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
            $recentGrades = $grades->sortByDesc('created_at')->take(5);
        }

        return $this->renderPage($request, 'students.dashboard', 'Dashboard', [
            'student' => $student,
            'stats' => $stats,
            'chartLabels' => $chartData->pluck('letter_grade')->values(),
            'chartValues' => $chartData->pluck('total')->values(),
            'recentGrades' => $recentGrades,
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
