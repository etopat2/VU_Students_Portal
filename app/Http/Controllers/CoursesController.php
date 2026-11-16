<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Lecturer;
use App\Models\Module;
use App\Models\StudyProgram;
use App\Http\Requests\StoreCourseRequest;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CoursesController extends Controller
{
    public function index(Request $request): View|Response
    {
        $courses = Course::query()
            ->with(['modules:id,code,title', 'studyPrograms:id,name'])
            ->orderBy('code')
            ->paginate(15)
            ->withQueryString();

        $data = [
            'courses' => $courses,
            'courseCount' => Course::count(),
            'creditCount' => Course::sum('credits'),
            'departmentCount' => Course::query()->distinct()->count('department'),
        ];

        return $this->respond($request, 'courses.index', $data, 'Courses');
    }

    public function create(Request $request): View|Response
    {
        return $this->respond($request, 'courses.createCourse', [
            ...$this->formData(),
        ], 'Add course');
    }

    public function store(StoreCourseRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $moduleIds = $validated['module_ids'] ?? [];
        $department = Department::query()->findOrFail($validated['department_id']);
        $lecturer = Lecturer::query()->findOrFail($validated['lecturer_id']);
        $course = Course::create([
            'code' => $validated['code'],
            'title' => $validated['title'],
            'department_id' => $department->id,
            'lecturer_id' => $lecturer->id,
            'department' => $department->name,
            'instructor' => $lecturer->name,
            'credits' => $validated['credits'],
            'schedule' => $validated['schedule'] ?? null,
            'status' => $validated['status'],
        ]);
        $course->studyPrograms()->sync($validated['study_program_ids']);
        $course->modules()->sync($moduleIds);

        ToastMagic::success('Course added successfully.');

        return redirect()->route('courses.index');
    }

    public function edit(Request $request, Course $course): View|Response
    {
        $course->load(['studyPrograms', 'modules']);

        return $this->respond($request, 'courses.editCourse', [
            'course' => $course,
            ...$this->formData(),
        ], 'Edit course');
    }

    public function update(StoreCourseRequest $request, Course $course): RedirectResponse
    {
        $validated = $request->validated();
        $moduleIds = $validated['module_ids'] ?? [];
        $department = Department::query()->findOrFail($validated['department_id']);
        $lecturer = Lecturer::query()->findOrFail($validated['lecturer_id']);
        $course->update([
            'code' => $validated['code'],
            'title' => $validated['title'],
            'department_id' => $department->id,
            'lecturer_id' => $lecturer->id,
            'department' => $department->name,
            'instructor' => $lecturer->name,
            'credits' => $validated['credits'],
            'schedule' => $validated['schedule'] ?? null,
            'status' => $validated['status'],
        ]);
        $course->studyPrograms()->sync($validated['study_program_ids']);
        $course->modules()->sync($moduleIds);

        ToastMagic::success('Course updated successfully.');

        return redirect()->route('courses.index');
    }

    public function destroy(Course $course): RedirectResponse
    {
        abort_unless(auth()->user()->hasAnyRole(['admin', 'registrar']), 403);
        $course->delete();

        ToastMagic::success('Course deleted successfully.');

        return redirect()->route('courses.index');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'faculties' => Faculty::query()->orderBy('name')->get(['id', 'name']),
            'departments' => Department::query()->orderBy('name')->get(['id', 'faculty_id', 'name']),
            'lecturers' => Lecturer::query()->orderBy('name')->get(['id', 'department_id', 'name']),
            'studyPrograms' => StudyProgram::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'modules' => Module::query()
                ->with('department.faculty:id,name')
                ->orderBy('title')
                ->get(['id', 'department_id', 'code', 'title', 'year_level', 'session_number']),
        ];
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
