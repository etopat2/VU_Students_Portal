<?php

namespace App\Http\Requests;

use App\Models\Assessment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateAssessmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $assessment = $this->route('assessment');

        return $assessment instanceof Assessment
            && ($this->user()?->can('update', $assessment) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('assessments', 'name')
                    ->where('module_id', $this->route('assessment')?->module_id)
                    ->where('session_id', $this->route('assessment')?->session_id)
                    ->ignore($this->route('assessment')),
            ],
            'assessment_type' => ['required', 'string', 'in:coursework,test,midterm,final_exam'],
            'max_score' => ['required', 'numeric', 'gt:0', 'decimal:0,2'],
            'weight_percentage' => ['required', 'numeric', 'gt:0', 'lte:100', 'decimal:0,2'],
            'due_date' => ['nullable', 'date'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date'],
            'question_file' => [
                Rule::requiredIf($this->route('assessment')?->question_file_path === null),
                'nullable',
                'file',
                'mimes:pdf,docx',
                'extensions:pdf,docx',
                'max:51200',
            ],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                if (Carbon::parse($this->input('ends_at'))->lessThanOrEqualTo(
                    Carbon::parse($this->input('starts_at')),
                )) {
                    $validator->errors()->add('ends_at', 'The end time must be after the start time.');

                    return;
                }

                $assessment = $this->route('assessment');

                if (! $assessment instanceof Assessment) {
                    return;
                }

                $usedWeight = (float) Assessment::query()
                    ->where('module_id', $assessment->module_id)
                    ->where('session_id', $assessment->session_id)
                    ->where('id', '!=', $assessment->id)
                    ->sum('weight_percentage');

                if ($usedWeight + (float) $this->input('weight_percentage') > 100) {
                    $validator->errors()->add('weight_percentage', 'Assessment weights for a module cannot exceed 100%.');
                }
            },
        ];
    }
}
