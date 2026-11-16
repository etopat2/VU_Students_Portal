<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Institution;
use App\Models\Module;
use App\Models\StudyProgram;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AcademicStructureController extends Controller
{
    public function index(Request $request, string $type): View|Response
    {
        [$model, $title] = $this->resource($type);
        $records = $model::query()
            ->with($this->relations($type))
            ->orderBy($type === 'modules' ? 'title' : 'name')
            ->paginate(15)
            ->withQueryString();

        return $this->respond($request, 'academic-structure.index', [
            'type' => $type,
            'title' => $title,
            'records' => $records,
        ], $title);
    }

    public function create(Request $request, string $type): View|Response
    {
        [, $title] = $this->resource($type);

        return $this->respond($request, 'academic-structure.form', [
            'type' => $type,
            'title' => $title,
            'record' => null,
            ...$this->options($type),
        ], 'Add '.$this->singular($type));
    }

    public function store(Request $request, string $type): RedirectResponse
    {
        [$model] = $this->resource($type);
        $validated = $request->validate($this->rules($type));
        $courseIds = $validated['course_ids'] ?? [];
        unset($validated['course_ids']);

        $record = $model::create($validated);

        if ($record instanceof Module) {
            $record->courses()->sync($courseIds);
        }

        ToastMagic::success($this->singular($type).' added successfully.');

        return redirect()->route('academic-structure.index', ['type' => $type]);
    }

    public function edit(Request $request, string $type, int $record): View|Response
    {
        [$model, $title] = $this->resource($type);
        $record = $model::query()->with($this->relations($type))->findOrFail($record);

        return $this->respond($request, 'academic-structure.form', [
            'type' => $type,
            'title' => $title,
            'record' => $record,
            ...$this->options($type),
        ], 'Edit '.$this->singular($type));
    }

    public function update(Request $request, string $type, int $record): RedirectResponse
    {
        [$model] = $this->resource($type);
        $record = $model::query()->findOrFail($record);
        $validated = $request->validate($this->rules($type, $record));
        $courseIds = $validated['course_ids'] ?? [];
        unset($validated['course_ids']);
        $record->update($validated);

        if ($record instanceof Module) {
            $record->courses()->sync($courseIds);
        }

        ToastMagic::success($this->singular($type).' updated successfully.');

        return redirect()->route('academic-structure.index', ['type' => $type]);
    }

    public function destroy(string $type, int $record): RedirectResponse
    {
        [$model] = $this->resource($type);
        $record = $model::query()->findOrFail($record);

        if ($this->hasDependentRecords($record)) {
            throw ValidationException::withMessages([
                'record' => 'This record is in use. Remove its related records before deleting it.',
            ]);
        }

        $record->delete();
        ToastMagic::success($this->singular($type).' removed successfully.');

        return redirect()->route('academic-structure.index', ['type' => $type]);
    }

    /**
     * @return array{class-string<Model>, string}
     */
    private function resource(string $type): array
    {
        return match ($type) {
            'faculties' => [Faculty::class, 'Faculties'],
            'departments' => [Department::class, 'Departments'],
            'study-programs' => [StudyProgram::class, 'Study Programs'],
            'modules' => [Module::class, 'Modules'],
            default => abort(404),
        };
    }

    /**
     * @return array<string, list<string>>
     */
    private function relations(string $type): array
    {
        return match ($type) {
            'faculties' => ['institution'],
            'departments' => ['faculty'],
            'study-programs' => ['courses'],
            'modules' => ['department', 'courses'],
            default => [],
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function options(string $type): array
    {
        return match ($type) {
            'faculties' => ['institutions' => Institution::query()->orderBy('name')->get(['id', 'name'])],
            'departments' => ['faculties' => Faculty::query()->orderBy('name')->get(['id', 'name'])],
            'modules' => [
                'departments' => Department::query()->orderBy('name')->get(['id', 'name']),
                'courses' => Course::query()->orderBy('title')->get(['id', 'department_id', 'code', 'title']),
            ],
            default => [],
        };
    }

    /**
     * @return array<string, array<mixed>|string>
     */
    private function rules(string $type, ?Model $record = null): array
    {
        return match ($type) {
            'faculties' => [
                'institution_id' => ['required', 'integer', 'exists:institutions,id'],
                'name' => ['required', 'string', 'max:255'],
                'code' => ['required', 'string', 'max:30', Rule::unique('faculties', 'code')->ignore($record)],
            ],
            'departments' => [
                'faculty_id' => ['required', 'integer', 'exists:faculties,id'],
                'name' => ['required', 'string', 'max:255'],
                'code' => ['required', 'string', 'max:30', Rule::unique('departments', 'code')->ignore($record)],
            ],
            'study-programs' => [
                'name' => ['required', 'string', 'max:50', Rule::unique('study_programs', 'name')->ignore($record)],
                'description' => ['nullable', 'string', 'max:255'],
                'is_active' => ['nullable', 'boolean'],
            ],
            'modules' => [
                'department_id' => ['required', 'integer', 'exists:departments,id'],
                'code' => ['required', 'string', 'max:30', Rule::unique('modules', 'code')->ignore($record)],
                'title' => ['required', 'string', 'max:255'],
                'credit_units' => ['required', 'integer', 'between:1,30'],
                'year_level' => ['required', 'integer', 'between:1,4'],
                'session_number' => ['required', 'integer', 'between:1,2'],
                // Modules may be created before they are assigned to courses.
                // Courses can also attach modules later from the course form.
                'course_ids' => ['nullable', 'array'],
                'course_ids.*' => ['required', 'integer', 'distinct', 'exists:courses,id'],
            ],
            default => abort(404),
        };
    }

    private function hasDependentRecords(Model $record): bool
    {
        return match (true) {
            $record instanceof Faculty => $record->departments()->exists(),
            $record instanceof Department => $record->courses()->exists()
                || $record->modules()->exists()
                || $record->lecturers()->exists(),
            $record instanceof StudyProgram => $record->courses()->exists()
                || $record->students()->exists(),
            $record instanceof Module => $record->studentRegistrations()->exists()
                || $record->assessments()->exists()
                || $record->teachingAllocations()->exists(),
            default => false,
        };
    }

    private function singular(string $type): string
    {
        return match ($type) {
            'faculties' => 'Faculty',
            'departments' => 'Department',
            'study-programs' => 'Study program',
            'modules' => 'Module',
            default => abort(404),
        };
    }

    private function respond(Request $request, string $view, array $data, string $pageTitle): View|Response
    {
        $page = view($view, $data);

        if ($request->ajax()) {
            return response($page->renderSections()['content'])
                ->header('X-Page-Title', $pageTitle);
        }

        return $page;
    }
}
