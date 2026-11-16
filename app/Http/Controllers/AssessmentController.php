<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAssessmentRequest;
use App\Http\Requests\UpdateAssessmentRequest;
use App\Models\Assessment;
use App\Models\Lecturer;
use App\Models\Module;
use App\Models\Session;
use App\Models\TeachingAllocation;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class AssessmentController extends Controller
{
    public function index(Request $request): View|Response
    {
        $isRegistrar = $request->user()->hasRole('registrar');
        $allocationsQuery = TeachingAllocation::query()
            ->with(['module', 'session.academicYear']);

        if (! $isRegistrar) {
            $lecturer = Lecturer::query()->where('user_id', $request->user()->id)->firstOrFail();
            $allocationsQuery->where('lecturer_id', $lecturer->id);
        } else {
            $allocationsQuery->with('lecturer:id,name');
        }

        $allocations = $allocationsQuery
            ->orderByDesc('session_id')
            ->get();

        $assessments = Assessment::query()
            ->with(['module', 'session'])
            ->whereIn('module_id', $allocations->pluck('module_id'))
            ->whereIn('session_id', $allocations->pluck('session_id'))
            ->get()
            ->groupBy(fn (Assessment $assessment): string => $assessment->module_id.':'.$assessment->session_id);

        $allocations = $allocations
            ->groupBy(fn (TeachingAllocation $allocation): string => $allocation->module_id.':'.$allocation->session_id)
            ->map(function ($moduleAllocations, string $key) use ($assessments, $isRegistrar): TeachingAllocation {
                $allocation = $moduleAllocations->first();
                $allocation->setRelation('assessments', $assessments->get($key, collect()));

                if ($isRegistrar) {
                    $allocation->setRelation(
                        'allocatedLecturers',
                        $moduleAllocations->pluck('lecturer')->unique('id')->values(),
                    );
                }

                return $allocation;
            })
            ->values();

        $page = view('assessments.index', compact('allocations', 'isRegistrar'));

        if ($request->ajax()) {
            return response($page->renderSections()['content'])
                ->header('X-Page-Title', 'Tests & examinations');
        }

        return $page;
    }

    public function store(
        StoreAssessmentRequest $request,
        Module $module,
        Session $session,
    ): RedirectResponse {
        $validated = $request->validated();
        $lecturerId = $validated['lecturer_id'] ?? $request->user()->lecturer()->value('id');
        $questionFile = $request->file('question_file');
        $questionPath = $questionFile ? $this->storeQuestionFile($questionFile) : null;
        unset($validated['question_file']);

        try {
            DB::transaction(function () use (
                $validated,
                $module,
                $session,
                $lecturerId,
                $questionFile,
                $questionPath,
            ): void {
                $usedWeight = (float) Assessment::query()
                    ->where('module_id', $module->id)
                    ->where('session_id', $session->id)
                    ->lockForUpdate()
                    ->sum('weight_percentage');

                if ($usedWeight + (float) $validated['weight_percentage'] > 100) {
                    throw ValidationException::withMessages([
                        'weight_percentage' => 'Assessment weights for a module cannot exceed 100%.',
                    ]);
                }

                $module->assessments()->create([
                    ...$validated,
                    'session_id' => $session->id,
                    'lecturer_id' => $lecturerId,
                    'question_file_path' => $questionPath,
                    'question_file_original_name' => $questionFile
                        ? $this->originalFilename($questionFile)
                        : null,
                ]);
            });
        } catch (Throwable $exception) {
            if ($questionPath !== null) {
                Storage::disk('local')->delete($questionPath);
            }

            throw $exception;
        }

        ToastMagic::success('Assessment created.');

        return back();
    }

    public function update(
        UpdateAssessmentRequest $request,
        Assessment $assessment,
    ): RedirectResponse {
        $validated = $request->validated();
        $this->authorize('update', $assessment);
        $questionFile = $request->file('question_file');
        $questionPath = $questionFile ? $this->storeQuestionFile($questionFile) : $assessment->question_file_path;
        unset($validated['question_file']);
        $previousQuestionPath = $assessment->question_file_path;

        try {
            DB::transaction(function () use ($assessment, $validated, $questionFile, $questionPath): void {
                $assessment = Assessment::query()->lockForUpdate()->findOrFail($assessment->id);
                $otherWeight = (float) Assessment::query()
                    ->where('module_id', $assessment->module_id)
                    ->where('session_id', $assessment->session_id)
                    ->where('id', '!=', $assessment->id)
                    ->sum('weight_percentage');

                if ($otherWeight + (float) $validated['weight_percentage'] > 100) {
                    throw ValidationException::withMessages([
                        'weight_percentage' => 'Assessment weights for a module cannot exceed 100%.',
                    ]);
                }

                $assessment->update([
                    ...$validated,
                    'question_file_path' => $questionPath,
                    'question_file_original_name' => $questionFile
                        ? $this->originalFilename($questionFile)
                        : $assessment->question_file_original_name,
                ]);
            });
        } catch (Throwable $exception) {
            if ($questionFile && $questionPath !== null) {
                Storage::disk('local')->delete($questionPath);
            }

            throw $exception;
        }

        if (
            $questionFile
            && $previousQuestionPath !== null
            && Storage::disk('local')->exists($previousQuestionPath)
            && ! Storage::disk('local')->delete($previousQuestionPath)
        ) {
            throw new RuntimeException('The previous assessment question file could not be removed.');
        }

        ToastMagic::success('Assessment updated.');

        return back();
    }

    private function storeQuestionFile(UploadedFile $file): string
    {
        $path = $file->store('assessment-questions', 'local');

        if (! is_string($path)) {
            throw new RuntimeException('The assessment question file could not be stored.');
        }

        return $path;
    }

    private function originalFilename(UploadedFile $file): string
    {
        $filename = preg_replace('/[\x00-\x1F\x7F]/', '_', basename($file->getClientOriginalName()));

        return Str::limit($filename ?: 'question-file', 255, '');
    }
}
