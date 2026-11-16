<?php

namespace App\Http\Requests;

use App\Models\AcademicYear;
use App\Models\Course;
use App\Models\StudyProgram;
use App\Models\StudySession;
use App\Models\Student;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['admin', 'registrar']) ?? false;
    }

    public function rules(): array
    {
        return [
            'student_number' => [
                'required',
                'string',
                'max:50',
                Rule::unique('students', 'student_number')->ignore($this->route('student')),
            ],
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('students', 'email')->ignore($this->route('student')),
            ],
            'status' => ['required', Rule::in(['Active', 'On leave', 'Graduated'])],
            'is_in_service_teacher' => ['nullable', 'boolean'],
            'study_program_id' => ['required', 'integer', 'exists:study_programs,id'],
            'study_session_id' => ['required', 'integer', 'exists:study_sessions,id'],
            'course_id' => ['required', 'integer', 'exists:courses,id'],
            'academic_year_id' => ['required', 'integer', 'exists:academic_years,id'],
            'assignment_reason' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['study_program_id', 'study_session_id', 'course_id', 'academic_year_id'])) {
                    return;
                }

                $course = Course::query()->find($this->integer('course_id'));
                $program = StudyProgram::query()->find($this->integer('study_program_id'));
                $academicYear = AcademicYear::query()->find($this->integer('academic_year_id'));
                $studySession = StudySession::query()->find($this->integer('study_session_id'));

                if (! $course || ! $program || ! $academicYear || ! $studySession) {
                    return;
                }

                if (! $studySession->is_active) {
                    $validator->errors()->add('study_session_id', 'Choose an active study session.');
                }
                if ($studySession->audience === 'in_service_teachers' && ! $this->boolean('is_in_service_teacher')) {
                    $validator->errors()->add('study_session_id', 'This session is available only to students marked as in-service teachers.');
                }

                if (! $course->studyPrograms()->whereKey($program->id)->exists()) {
                    $validator->errors()->add('study_program_id', 'Choose a study program offered for this course.');
                }
                if (! $program->is_active) {
                    $validator->errors()->add('study_program_id', 'Choose an active study program.');
                }

                $existingStudent = $this->route('student');
                if ($existingStudent instanceof Student) {
                    $assignmentChanged = (int) $existingStudent->study_session_id !== (int) $this->integer('study_session_id')
                        || (int) $existingStudent->study_program_id !== (int) $this->integer('study_program_id');

                    if ($assignmentChanged && blank($this->input('assignment_reason'))) {
                        $validator->errors()->add('assignment_reason', 'Explain why the study session or program is changing.');
                    }
                }

                if ($academicYear->course_id !== $course->id) {
                    $validator->errors()->add(
                        'academic_year_id',
                        'Choose a year of study that belongs to the selected course.',
                    );
                }
            },
        ];
    }
}
