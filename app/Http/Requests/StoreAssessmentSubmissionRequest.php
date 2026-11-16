<?php

namespace App\Http\Requests;

use App\Models\Assessment;
use App\Models\AssessmentSubmission;
use App\Support\AssessmentAnswerContent;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreAssessmentSubmissionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $assessment = $this->route('assessment');

        return $assessment instanceof Assessment
            && ($this->user()?->can('submit', $assessment) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'answer_content' => ['nullable', 'string', 'max:100000'],
            'answer_file' => [
                'nullable',
                'file',
                'mimes:pdf,docx,xlsx,jpg,jpeg,png',
                'extensions:pdf,docx,xlsx,jpg,jpeg,png',
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

                $answerContent = AssessmentAnswerContent::sanitize((string) $this->input('answer_content', ''));

                if (AssessmentAnswerContent::hasText($answerContent) || $this->hasFile('answer_file')) {
                    return;
                }

                $assessment = $this->route('assessment');
                $studentId = $this->user()?->student?->id;

                if (! $assessment instanceof Assessment || $studentId === null) {
                    return;
                }

                $existingSubmissionHasFile = AssessmentSubmission::query()
                    ->where('assessment_id', $assessment->id)
                    ->whereHas('studentModuleRegistration', function ($query) use ($assessment, $studentId): void {
                        $query->where('module_id', $assessment->module_id)
                            ->whereHas('semesterRegistration', fn ($query) => $query
                                ->where('student_id', $studentId)
                                ->where('session_id', $assessment->session_id));
                    })
                    ->whereNotNull('answer_file_path')
                    ->exists();

                if (! $existingSubmissionHasFile) {
                    $validator->errors()->add(
                        'answer_content',
                        'Enter an answer or attach an answer sheet before submitting.',
                    );
                }
            },
        ];
    }
}
