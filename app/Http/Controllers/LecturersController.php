<?php

namespace App\Http\Controllers;

use App\Models\Lecturer;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class LecturersController extends Controller
{
    public function index(Request $request): View|Response
    {
        $lecturers = Lecturer::query()
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return $this->respond($request, 'lecturers.index', [
            'lecturers' => $lecturers,
            'lecturerCount' => Lecturer::count(),
            'activeCount' => Lecturer::where('status', 'Active')->count(),
            'departmentCount' => Lecturer::query()->distinct()->count('department'),
        ], 'Lecturers');
    }

    public function create(Request $request): View|Response
    {
        return $this->respond($request, 'lecturers.createLecturer', [
            'lecturer' => null,
        ], 'Add lecturer');
    }

    public function store(Request $request): RedirectResponse
    {
        Lecturer::create($this->validatedLecturer($request));

        return redirect()
            ->route('lecturers.index')
            ->with('status', 'Lecturer added successfully.');
    }

    public function edit(Request $request, Lecturer $lecturer): View|Response
    {
        return $this->respond($request, 'lecturers.editLecturer', compact('lecturer'), 'Edit lecturer');
    }

    public function update(Request $request, Lecturer $lecturer): RedirectResponse
    {
        $lecturer->update($this->validatedLecturer($request, $lecturer));

        return redirect()
            ->route('lecturers.index')
            ->with('status', 'Lecturer updated successfully.');
    }

    public function destroy(Lecturer $lecturer): RedirectResponse
    {
        $lecturer->delete();

        return redirect()
            ->route('lecturers.index')
            ->with('status', 'Lecturer removed successfully.');
    }

    private function validatedLecturer(Request $request, ?Lecturer $lecturer = null): array
    {
        return $request->validate([
            'lecturer_number' => [
                'required',
                'string',
                'max:50',
                Rule::unique('lecturers', 'lecturer_number')->ignore($lecturer),
            ],
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('lecturers', 'email')->ignore($lecturer),
            ],
            'department' => ['required', 'string', 'max:255'],
            'qualification' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'status' => ['required', Rule::in(['Active', 'On leave'])],
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
