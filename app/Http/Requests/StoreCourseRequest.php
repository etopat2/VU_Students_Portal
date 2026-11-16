<?php

namespace App\Http\Requests;

use App\Models\Department;
use App\Models\Faculty;
use App\Models\Lecturer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['admin', 'registrar']) ?? false;
    }

    public function rules(): array
    {
        return [
            'code' => [
                'required',
                'string',
                'max:30',
                Rule::unique('courses', 'code')->ignore($this->route('course')),
            ],
            'title' => ['required', 'string', 'max:255'],
            'faculty_id' => ['required', 'integer', 'exists:faculties,id'],
            'department_id' => ['required', 'integer', 'exists:departments,id'],
            'lecturer_id' => ['required', 'integer', 'exists:lecturers,id'],
            'study_program_ids' => ['required', 'array', 'min:1'],
            'study_program_ids.*' => ['required', 'integer', 'distinct', 'exists:study_programs,id'],
            'module_ids' => ['nullable', 'array'],
            'module_ids.*' => ['required', 'integer', 'distinct', 'exists:modules,id'],
            'credits' => ['required', 'integer', 'between:1,30'],
            'schedule' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(['In progress', 'Upcoming', 'Completed'])],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['faculty_id', 'department_id', 'lecturer_id'])) {
                    return;
                }

                $faculty = Faculty::query()->find($this->integer('faculty_id'));
                $department = Department::query()->find($this->integer('department_id'));
                $lecturer = Lecturer::query()->find($this->integer('lecturer_id'));

                if ($faculty && $department && $department->faculty_id !== $faculty->id) {
                    $validator->errors()->add('department_id', 'Choose a department in the selected faculty.');
                }

                if (
                    $lecturer
                    && $department
                    && $lecturer->department_id !== null
                    && $lecturer->department_id !== $department->id
                ) {
                    $validator->errors()->add('lecturer_id', 'Choose a lecturer from the selected department.');
                }
            },
        ];
    }
}
