<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class CoursesController extends Controller
{
    public function index(Request $request): View|Response
    {
        $courses = Course::query()
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
        return $this->respond($request, 'courses.createCourse', [], 'Add course');
    }

    public function store(Request $request): RedirectResponse
    {
        Course::create($this->validatedCourse($request));

        return redirect()
            ->route('courses.index')
            ->with('status', 'Course added successfully.');
    }

    public function edit(Request $request, Course $course): View|Response
    {
        return $this->respond($request, 'courses.editCourse', compact('course'), 'Edit course');
    }

    public function update(Request $request, Course $course): RedirectResponse
    {
        $course->update($this->validatedCourse($request, $course));

        return redirect()
            ->route('courses.index')
            ->with('status', 'Course updated successfully.');
    }

    public function destroy(Course $course): RedirectResponse
    {
        $course->delete();

        return redirect()
            ->route('courses.index')
            ->with('status', 'Course deleted successfully.');
    }

    private function validatedCourse(Request $request, ?Course $course = null): array
    {
        return $request->validate([
            'code' => ['required', 'string', 'max:30', Rule::unique('courses', 'code')->ignore($course)],
            'title' => ['required', 'string', 'max:255'],
            'department' => ['required', 'string', 'max:255'],
            'instructor' => ['required', 'string', 'max:255'],
            'credits' => ['required', 'integer', 'between:1,30'],
            'schedule' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(['In progress', 'Upcoming', 'Completed'])],
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
