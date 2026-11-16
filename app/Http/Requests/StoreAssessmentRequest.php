<?php

namespace App\Http\Requests;

use App\Models\Assessment;
use App\Models\Module;
use App\Models\Session;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreAssessmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $module = $this->route('module');
        $session = $this->route('session');

        return $module instanceof Module
            && $session instanceof Session
            && ($this->user()?->can('create', [Assessment::class, $module, $session]) ?? false);
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
                    ->where('module_id', $this->route('module')?->id)
                    ->where('session_id', $this->route('session')?->id),
            ],
            'assessment_type' => ['required', 'string', 'in:coursework,test,midterm,final_exam'],
            'max_score' => ['required', 'numeric', 'gt:0', 'decimal:0,2'],
            'weight_percentage' => ['required', 'numeric', 'gt:0', 'lte:100', 'decimal:0,2'],
            'lecturer_id' => [
                Rule::requiredIf($this->user()?->hasRole('registrar')),
                'nullable',
                'integer',
                'exists:lecturers,id',
            ],
            'due_date' => ['nullable', 'date'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date'],
            'question_file' => [
                'required',
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

                $module = $this->route('module');
                $session = $this->route('session');

                if (! $module instanceof Module || ! $session instanceof Session) {
                    return;
                }

                $session->loadMissing('academicYear');

                if (
                    $module->year_level !== $session->academicYear->year_level
                    || $module->session_number !== $session->number
                ) {
                    $validator->errors()->add('assessment_type', 'The module is not offered in this session.');

                    return;
                }

                $lecturerId = $this->input('lecturer_id')
                    ?? $this->user()?->lecturer()->value('id');

                $assigned = $lecturerId !== null
                    && $module->teachingAllocations()
                        ->where('session_id', $session->id)
                        ->where('lecturer_id', $lecturerId)
                        ->exists();

                if (! $assigned) {
                    $validator->errors()->add('lecturer_id', 'The lecturer must be assigned to this module and session.');

                    return;
                }

                $usedWeight = (float) Assessment::query()
                    ->where('module_id', $module->id)
                    ->where('session_id', $session->id)
                    ->sum('weight_percentage');

                if ($usedWeight + (float) $this->input('weight_percentage') > 100) {
                    $validator->errors()->add('weight_percentage', 'Assessment weights for a module cannot exceed 100%.');
                }
            },
        ];
    }
}
