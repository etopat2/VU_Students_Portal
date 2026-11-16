<?php

namespace App\Policies;

use App\Models\Assessment;
use App\Models\AssessmentSubmission;
use App\Models\Module;
use App\Models\Session;
use App\Models\StudentModuleRegistration;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class AssessmentPolicy
{
    public function create(User $user, Module $module, Session $session): bool
    {
        if ($user->hasRole('registrar')) {
            return true;
        }

        return $user->hasRole('lecturer')
            && $this->assignedTo($user, $module->id, $session->id);
    }

    public function update(User $user, Assessment $assessment): bool
    {
        if ($user->hasRole('registrar')) {
            return true;
        }

        return $user->hasRole('lecturer')
            && $this->assignedTo($user, $assessment->module_id, $assessment->session_id);
    }

    public function recordResult(User $user, Assessment $assessment): bool
    {
        return $this->update($user, $assessment);
    }

    public function viewForStudent(User $user, Assessment $assessment): bool
    {
        return $this->isRegisteredForAssessment($user, $assessment);
    }

    public function submit(User $user, Assessment $assessment): bool
    {
        return $this->isRegisteredForAssessment($user, $assessment)
            && $assessment->submissionWindowIsOpen();
    }

    public function downloadQuestion(User $user, Assessment $assessment): bool
    {
        return $this->isRegisteredForAssessment($user, $assessment)
            && $assessment->submissionWindowIsOpen();
    }

    public function downloadOwnSubmission(
        User $user,
        Assessment $assessment,
        AssessmentSubmission $submission,
    ): bool {
        return $this->isRegisteredForAssessment($user, $assessment)
            && $this->submissionBelongsToAssessment($assessment, $submission)
            && $submission->studentModuleRegistration
                ->semesterRegistration
                ->student_id === $user->student?->id;
    }

    public function viewSubmissions(User $user, Assessment $assessment): bool
    {
        return $this->update($user, $assessment);
    }

    public function downloadSubmission(User $user, Assessment $assessment, AssessmentSubmission $submission): bool
    {
        return $this->update($user, $assessment)
            && $this->submissionBelongsToAssessment($assessment, $submission);
    }

    private function isRegisteredForAssessment(User $user, Assessment $assessment): bool
    {
        if (
            ! $user->hasRole('student')
            || $user->student === null
            || $assessment->starts_at === null
            || $assessment->ends_at === null
            || $assessment->question_file_path === null
        ) {
            return false;
        }

        return StudentModuleRegistration::query()
            ->where('module_id', $assessment->module_id)
            ->where('status', 'registered')
            ->whereHas('semesterRegistration', function (Builder $query) use ($user, $assessment): void {
                $query->where('student_id', $user->student->id)
                    ->where('session_id', $assessment->session_id)
                    ->where('status', 'registered');
            })
            ->exists();
    }

    private function submissionBelongsToAssessment(
        Assessment $assessment,
        AssessmentSubmission $submission,
    ): bool {
        return $submission->assessment_id === $assessment->id
            && $submission->studentModuleRegistration()
                ->where('module_id', $assessment->module_id)
                ->whereHas('semesterRegistration', fn (Builder $query) => $query
                    ->where('session_id', $assessment->session_id))
                ->exists();
    }

    private function assignedTo(User $user, int $moduleId, int $sessionId): bool
    {
        return $user->lecturer()
            ->whereHas('teachingAllocations', function (Builder $query) use ($moduleId, $sessionId): void {
                $query->where('module_id', $moduleId)->where('session_id', $sessionId);
            })
            ->exists();
    }
}
