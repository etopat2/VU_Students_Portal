<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Grade;
use App\Models\Student;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class GradesController extends Controller
{
    public function index(Request $request): View|Response
    {
        $grades = Grade::query()
            ->with(['student:id,name,student_number', 'course:id,code,title'])
            ->orderByDesc('term')
            ->orderBy('course_id')
            ->paginate(15)
            ->withQueryString();

        $data = [
            'grades' => $grades,
            'gradeCount' => Grade::count(),
            'averageScore' => round((float) Grade::avg('score'), 1),
            'highestScore' => Grade::max('score') ?? 0,
        ];

        return $this->respond($request, 'grades.index', $data, 'Grades');
    }

    public function create(Request $request): View|Response
    {
        return $this->gradeForm($request, 'grades.addGrade', 'Add grade');
    }

    public function store(Request $request): RedirectResponse
    {
        Grade::create($this->validatedGrade($request));

        ToastMagic::success('Grade added successfully.');

        return redirect()->route('grades.index');
    }

    public function edit(Request $request, Grade $grade): View|Response
    {
        return $this->gradeForm($request, 'grades.editGrade', 'Edit grade', compact('grade'));
    }

    public function update(Request $request, Grade $grade): RedirectResponse
    {
        $grade->update($this->validatedGrade($request, $grade));

        ToastMagic::success('Grade updated successfully.');

        return redirect()->route('grades.index');
    }

    public function destroy(Grade $grade): RedirectResponse
    {
        $grade->delete();

        ToastMagic::success('Grade deleted successfully.');

        return redirect()->route('grades.index');
    }

    private function validatedGrade(Request $request, ?Grade $grade = null): array
    {
        return $request->validate([
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'course_id' => ['required', 'integer', 'exists:courses,id'],
            'assessment' => [
                'required',
                'string',
                'max:100',
                Rule::unique('grades', 'assessment')
                    ->where(fn ($query) => $query
                        ->where('student_id', $request->input('student_id'))
                        ->where('course_id', $request->input('course_id'))
                        ->where('term', $request->input('term')))
                    ->ignore($grade),
            ],
            'score' => ['required', 'numeric', 'between:0,100'],
            'letter_grade' => ['required', Rule::in(['A', 'B+', 'B', 'C+', 'C', 'D', 'F'])],
            'term' => ['required', 'string', 'max:100'],
        ]);
    }

    private function gradeForm(Request $request, string $view, string $title, array $data = []): View|Response
    {
        $data['students'] = Student::query()->orderBy('name')->get(['id', 'name', 'student_number']);
        $data['courses'] = Course::query()->orderBy('code')->get(['id', 'code', 'title']);

        return $this->respond($request, $view, $data, $title);
    }

    private function respond(Request $request, string $view, array $data, string $title): View|Response
    {
        $page = view($view, $data);

        if ($request->ajax()) {
            return response($page->renderSections()['content'])
                ->header('X-Page-Title', $title);
        }

        return $page;
    }
}
