<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAssessmentResultRequest;
use App\Models\Assessment;
use App\Models\AssessmentResult;
use App\Models\FinalModuleResult;
use App\Models\GradeScale;
use App\Models\StudentModuleRegistration;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class AssessmentResultController extends Controller
{
    public function index(Request $request, Assessment $assessment): View|Response
    {
        $this->authorize('recordResult', $assessment);
        $assessment->load(['module', 'session']);
        $registrations = StudentModuleRegistration::query()
            ->where('module_id', $assessment->module_id)
            ->whereHas('semesterRegistration', fn ($query) => $query
                ->where('session_id', $assessment->session_id))
            ->with([
                'semesterRegistration.student',
                'assessmentResults' => fn ($query) => $query->where('assessment_id', $assessment->id),
            ])
            ->orderBy('id')
            ->paginate(50)
            ->withQueryString();

        $page = view('assessments.results', compact('assessment', 'registrations'));

        if ($request->ajax()) {
            return response($page->renderSections()['content'])
                ->header('X-Page-Title', 'Enter assessment results');
        }

        return $page;
    }

    public function store(
        StoreAssessmentResultRequest $request,
        Assessment $assessment,
    ): RedirectResponse {
        $validated = $request->validated();
        $this->authorize('recordResult', $assessment);

        $finalResultWasUpdated = DB::transaction(function () use ($request, $assessment, $validated): bool {
            $registration = StudentModuleRegistration::query()
                ->lockForUpdate()
                ->findOrFail($validated['student_module_registration_id']);

            AssessmentResult::updateOrCreate(
                [
                    'student_module_registration_id' => $registration->id,
                    'assessment_id' => $assessment->id,
                ],
                [
                    'entered_by' => $request->user()->id,
                    'score' => $validated['score'],
                    'feedback' => $validated['feedback'] ?? null,
                    'entered_at' => now(),
                ],
            );

            return $this->refreshFinalResult($assessment, $registration, $request->user()->id);
        });

        ToastMagic::success('Assessment result saved.');

        if (! $finalResultWasUpdated) {
            ToastMagic::info(
                'Final result is pending.',
                'All module assessments, a 100% weight total, and a matching grade scale are required.',
            );
        }

        return back();
    }

    private function refreshFinalResult(
        Assessment $assessment,
        StudentModuleRegistration $registration,
        int $userId,
    ): bool {
        $assessments = Assessment::query()
            ->where('module_id', $assessment->module_id)
            ->where('session_id', $assessment->session_id)
            ->with(['results' => fn ($query) => $query
                ->where('student_module_registration_id', $registration->id)
                ->with('assessment')])
            ->get();

        $results = $assessments->flatMap(fn (Assessment $item) => $item->results);

        if (
            $assessments->isEmpty()
            || $results->count() !== $assessments->count()
            || round((float) $assessments->sum('weight_percentage'), 2) !== 100.0
        ) {
            return false;
        }

        $courseworkScore = 0.0;
        $examScore = 0.0;

        foreach ($results as $result) {
            $weightedScore = ((float) $result->score / (float) $result->assessment->max_score)
                * (float) $result->assessment->weight_percentage;

            if ($result->assessment->assessment_type === 'final_exam') {
                $examScore += $weightedScore;
            } else {
                $courseworkScore += $weightedScore;
            }
        }

        $finalMark = round($courseworkScore + $examScore, 2);
        $gradeScale = GradeScale::query()
            ->where('minimum_mark', '<=', $finalMark)
            ->orderByDesc('minimum_mark')
            ->first();

        if (! $gradeScale) {
            return false;
        }

        FinalModuleResult::updateOrCreate(
            ['student_module_registration_id' => $registration->id],
            [
                'calculated_by' => $userId,
                'coursework_score' => round($courseworkScore, 2),
                'exam_score' => round($examScore, 2),
                'final_mark' => $finalMark,
                'letter_grade' => $gradeScale->letter_grade,
                'grade_point' => $gradeScale->grade_point,
                'calculated_at' => now(),
            ],
        );

        return true;
    }
}
