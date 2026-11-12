<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class StudentsController extends Controller
{
    public function index(Request $request): View|Response
    {
        $students = Student::query()
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $data = [
            'students' => $students,
            'studentCount' => Student::count(),
            'activeCount' => Student::where('status', 'Active')->count(),
            'programmeCount' => Student::query()->distinct()->count('programme'),
        ];

        return $this->respond($request, 'students.index', $data, 'Students');
    }

    public function create(Request $request): View|Response
    {
        return $this->respond($request, 'students.createStudent', [], 'Add student');
    }

    public function store(Request $request): RedirectResponse
    {
        Student::create($this->validatedStudent($request));

        return redirect()
            ->route('students.index')
            ->with('status', 'Student added successfully.');
    }

    public function edit(Request $request, Student $student): View|Response
    {
        return $this->respond($request, 'students.editStudent', compact('student'), 'Edit student');
    }

    public function update(Request $request, Student $student): RedirectResponse
    {
        $student->update($this->validatedStudent($request, $student));

        return redirect()
            ->route('students.index')
            ->with('status', 'Student updated successfully.');
    }

    public function destroy(Student $student): RedirectResponse
    {
        $student->delete();

        return redirect()
            ->route('students.index')
            ->with('status', 'Student deleted successfully.');
    }

    private function validatedStudent(Request $request, ?Student $student = null): array
    {
        return $request->validate([
            'student_number' => ['required', 'string', 'max:50', Rule::unique('students', 'student_number')->ignore($student)],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('students', 'email')->ignore($student)],
            'programme' => ['required', 'string', 'max:255'],
            'year' => ['required', 'string', 'max:50'],
            'status' => ['required', Rule::in(['Active', 'On leave', 'Graduated'])],
        ]);
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
