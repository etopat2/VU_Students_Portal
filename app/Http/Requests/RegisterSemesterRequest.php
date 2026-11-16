<?php

namespace App\Http\Requests;

use App\Models\Module;
use App\Models\SemesterRegistration;
use App\Models\Session;
use App\Models\Student;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class RegisterSemesterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $student = $this->route('student');

        return $student instanceof Student
            && ($this->user()?->can('register', $student) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'session_id' => ['required', 'integer', 'exists:academic_sessions,id'],
            'module_ids' => ['required', 'array', 'min:1'],
            'module_ids.*' => ['required', 'integer', 'distinct', 'exists:modules,id'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $student = $this->route('student');
                $session = Session::query()->with('academicYear')->find($this->integer('session_id'));

                if (! $student instanceof Student || ! $session || ! $student->course_id) {
                    $validator->errors()->add('session_id', 'The student must have a primary course before registering.');

                    return;
                }

                if ($session->academicYear->course_id !== $student->course_id) {
                    $validator->errors()->add('session_id', 'This session is not part of the student’s current course.');

                    return;
                }

                if (! $session->is_open) {
                    $validator->errors()->add('session_id', 'This session is not open for registration.');

                    return;
                }

                $hasFeeStructure = \App\Models\FeeStructure::query()
                    ->where('course_id', $student->course_id)
                    ->where('academic_year_id', $session->academic_year_id)
                    ->where('session_id', $session->id)
                    ->exists();

                if (! $hasFeeStructure) {
                    $validator->errors()->add('session_id', 'A fee structure must be set before registration.');

                    return;
                }

                $moduleIds = $this->input('module_ids', []);
                $eligibleModuleCount = Module::query()
                    ->whereIn('id', $moduleIds)
                    ->where('year_level', $session->academicYear->year_level)
                    ->where('session_number', $session->number)
                    ->whereHas('courses', fn ($query) => $query->whereKey($student->course_id))
                    ->count();

                if ($eligibleModuleCount !== count($moduleIds)) {
                    $validator->errors()->add('module_ids', 'Choose modules offered for this course and session.');
                }

                $alreadyRegistered = SemesterRegistration::query()
                    ->where('student_id', $student->id)
                    ->where('session_id', $session->id)
                    ->exists();

                if ($alreadyRegistered) {
                    $validator->errors()->add('session_id', 'The student is already registered for this session.');
                }
            },
        ];
    }
}
