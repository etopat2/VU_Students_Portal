<?php

namespace App\Http\Requests;

use App\Models\Assessment;
use App\Models\AssessmentResult;
use App\Models\StudentModuleRegistration;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreAssessmentResultRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $assessment = $this->route('assessment');
        $registration = StudentModuleRegistration::query()
            ->find($this->integer('student_module_registration_id'));

        return $assessment instanceof Assessment
            && $registration !== null
            && ($this->user()?->can('create', [AssessmentResult::class, $assessment, $registration]) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'student_module_registration_id' => [
                'required',
                'integer',
                'exists:student_module_registrations,id',
            ],
            'score' => ['required', 'numeric', 'gte:0', 'decimal:0,2'],
            'feedback' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $assessment = $this->route('assessment');
                $registration = StudentModuleRegistration::query()
                    ->with(['semesterRegistration', 'module'])
                    ->find($this->integer('student_module_registration_id'));

                if (! $assessment instanceof Assessment || ! $registration) {
                    return;
                }

                if (
                    $registration->module_id !== $assessment->module_id
                    || $registration->semesterRegistration->session_id !== $assessment->session_id
                ) {
                    $validator->errors()->add(
                        'student_module_registration_id',
                        'This student is not registered for the assessment module and session.',
                    );
                }

                if ((float) $this->input('score') > (float) $assessment->max_score) {
                    $validator->errors()->add('score', 'The score cannot exceed the assessment maximum.');
                }
            },
        ];
    }
}
