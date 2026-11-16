<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAssessmentSubmissionRequest;
use App\Models\Assessment;
use App\Models\AssessmentSubmission;
use App\Models\StudentModuleRegistration;
use App\Support\AssessmentAnswerContent;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class AssessmentSubmissionController extends Controller
{
    public function index(Request $request): View|Response
    {
        $student = $request->user()->student()->firstOrFail();
        $assessments = Assessment::query()
            ->with([
                'module',
                'session',
                'submissions' => fn ($query) => $query->whereHas(
                    'studentModuleRegistration.semesterRegistration',
                    fn ($query) => $query->where('student_id', $student->id),
                ),
            ])
            ->whereNotNull('starts_at')
            ->whereNotNull('ends_at')
            ->whereNotNull('question_file_path')
            ->whereHas('module.studentRegistrations', function ($query) use ($student): void {
                $query->where('student_module_registrations.status', 'registered')
                    ->whereHas('semesterRegistration', fn ($query) => $query
                        ->where('student_id', $student->id)
                        ->where('semester_registrations.status', 'registered')
                        ->whereColumn('semester_registrations.session_id', 'assessments.session_id'));
            })
            ->orderBy('starts_at')
            ->orderBy('name')
            ->get();

        return $this->renderPage($request, 'student-assessments.index', 'Tests & examinations', [
            'assessments' => $assessments,
        ]);
    }

    public function show(Request $request, Assessment $assessment): View|Response
    {
        $this->authorize('viewForStudent', $assessment);

        $student = $request->user()->student()->firstOrFail();
        $registration = $this->registeredModuleForStudent($assessment, $student->id);
        $assessment->load(['module', 'session']);
        $submission = $assessment->submissions()
            ->where('student_module_registration_id', $registration->id)
            ->first();

        return $this->renderPage($request, 'student-assessments.show', $assessment->name, [
            'assessment' => $assessment,
            'submission' => $submission,
        ]);
    }

    public function store(
        StoreAssessmentSubmissionRequest $request,
        Assessment $assessment,
    ): RedirectResponse {
        $student = $request->user()->student()->firstOrFail();
        $registration = $this->registeredModuleForStudent($assessment, $student->id);
        $validated = $request->validated();
        $answerFile = $request->file('answer_file');
        $answerPath = $answerFile
            ? $this->storeAnswerFile($answerFile, $assessment->id)
            : null;
        $previousAnswerPath = null;

        try {
            DB::transaction(function () use (
                $assessment,
                $registration,
                $validated,
                $answerFile,
                $answerPath,
                &$previousAnswerPath,
            ): void {
                abort_unless($assessment->submissionWindowIsOpen(), 403);

                $lockedRegistration = StudentModuleRegistration::query()
                    ->lockForUpdate()
                    ->findOrFail($registration->id);
                $submission = AssessmentSubmission::query()
                    ->where('assessment_id', $assessment->id)
                    ->where('student_module_registration_id', $lockedRegistration->id)
                    ->lockForUpdate()
                    ->first();
                $previousAnswerPath = $submission?->answer_file_path;
                $answerContent = AssessmentAnswerContent::sanitize((string) ($validated['answer_content'] ?? ''));
                $hasAnswerText = AssessmentAnswerContent::hasText($answerContent);

                if (! $hasAnswerText && $answerPath === null && $submission?->answer_file_path === null) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'answer_content' => 'Enter an answer or attach an answer sheet before submitting.',
                    ]);
                }

                AssessmentSubmission::updateOrCreate(
                    [
                        'assessment_id' => $assessment->id,
                        'student_module_registration_id' => $lockedRegistration->id,
                    ],
                    [
                        'answer_content' => $hasAnswerText ? $answerContent : null,
                        'answer_file_path' => $answerPath ?? $submission?->answer_file_path,
                        'answer_file_original_name' => $answerFile
                            ? $this->originalFilename($answerFile)
                            : $submission?->answer_file_original_name,
                        'submitted_at' => now(),
                    ],
                );
            });
        } catch (Throwable $exception) {
            if ($answerPath !== null) {
                Storage::disk('local')->delete($answerPath);
            }

            throw $exception;
        }

        if (
            $answerPath !== null
            && $previousAnswerPath !== null
            && Storage::disk('local')->exists($previousAnswerPath)
            && ! Storage::disk('local')->delete($previousAnswerPath)
        ) {
            throw new RuntimeException('The previous answer file could not be removed.');
        }

        ToastMagic::success('Your assessment answer has been submitted.');

        return redirect()->route('student-assessments.show', $assessment);
    }

    public function question(Assessment $assessment): StreamedResponse
    {
        $this->authorize('downloadQuestion', $assessment);

        abort_unless(
            $assessment->question_file_path !== null
                && Storage::disk('local')->exists($assessment->question_file_path),
            404,
        );

        return Storage::disk('local')->download(
            $assessment->question_file_path,
            $this->safeDownloadName($assessment->question_file_original_name, 'assessment-question'),
            [
                'Content-Type' => 'application/octet-stream',
                'X-Content-Type-Options' => 'nosniff',
            ],
        );
    }

    public function ownAnswer(Request $request, Assessment $assessment): StreamedResponse
    {
        $student = $request->user()->student()->firstOrFail();
        $registration = $this->registeredModuleForStudent($assessment, $student->id);
        $submission = $assessment->submissions()
            ->where('student_module_registration_id', $registration->id)
            ->firstOrFail();

        $this->authorize('downloadOwnSubmission', [$assessment, $submission]);

        abort_unless(
            $submission->answer_file_path !== null
                && Storage::disk('local')->exists($submission->answer_file_path),
            404,
        );

        return Storage::disk('local')->download(
            $submission->answer_file_path,
            $this->safeDownloadName($submission->answer_file_original_name, 'assessment-answer'),
            [
                'Content-Type' => 'application/octet-stream',
                'X-Content-Type-Options' => 'nosniff',
            ],
        );
    }

    public function report(Request $request, Assessment $assessment): View|Response
    {
        $this->authorize('viewSubmissions', $assessment);

        $assessment->load(['module', 'session']);
        $registrations = StudentModuleRegistration::query()
            ->where('module_id', $assessment->module_id)
            ->where('status', 'registered')
            ->whereHas('semesterRegistration', fn ($query) => $query
                ->where('session_id', $assessment->session_id)
                ->where('status', 'registered'))
            ->with([
                'semesterRegistration.student',
                'assessmentSubmissions' => fn ($query) => $query
                    ->where('assessment_id', $assessment->id),
            ])
            ->orderBy('id')
            ->paginate(50)
            ->withQueryString();

        return $this->renderPage($request, 'assessments.submissions', 'Submission status', [
            'assessment' => $assessment,
            'registrations' => $registrations,
        ]);
    }

    public function downloadAnswer(Assessment $assessment, AssessmentSubmission $submission): StreamedResponse
    {
        $this->authorize('downloadSubmission', [$assessment, $submission]);

        abort_unless(
            $submission->answer_file_path !== null
                && Storage::disk('local')->exists($submission->answer_file_path),
            404,
        );

        return Storage::disk('local')->download(
            $submission->answer_file_path,
            $this->safeDownloadName($submission->answer_file_original_name, 'assessment-answer'),
            [
                'Content-Type' => 'application/octet-stream',
                'X-Content-Type-Options' => 'nosniff',
            ],
        );
    }

    private function registeredModuleForStudent(Assessment $assessment, int $studentId): StudentModuleRegistration
    {
        return StudentModuleRegistration::query()
            ->where('module_id', $assessment->module_id)
            ->where('status', 'registered')
            ->whereHas('semesterRegistration', fn ($query) => $query
                ->where('student_id', $studentId)
                ->where('session_id', $assessment->session_id)
                ->where('status', 'registered'))
            ->firstOrFail();
    }

    private function storeAnswerFile(UploadedFile $file, int $assessmentId): string
    {
        $path = $file->store('assessment-submissions/'.$assessmentId, 'local');

        if (! is_string($path)) {
            throw new RuntimeException('The assessment answer file could not be stored.');
        }

        return $path;
    }

    private function originalFilename(UploadedFile $file): string
    {
        $filename = preg_replace('/[\x00-\x1F\x7F]/', '_', basename($file->getClientOriginalName()));

        return Str::limit($filename ?: 'assessment-answer', 255, '');
    }

    private function safeDownloadName(?string $filename, string $fallback): string
    {
        $name = preg_replace('/[\x00-\x1F\x7F]/', '_', basename($filename ?? ''));

        return Str::limit($name ?: $fallback, 255, '');
    }

    private function renderPage(Request $request, string $view, string $title, array $data = []): View|Response
    {
        $page = view($view, $data);

        if ($request->ajax()) {
            return response($page->renderSections()['content'])
                ->header('X-Page-Title', $title);
        }

        return $page;
    }
}
