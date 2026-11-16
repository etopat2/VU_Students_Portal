<?php

namespace App\Policies;

use App\Models\Assessment;
use App\Models\StudentModuleRegistration;
use App\Models\User;

class AssessmentResultPolicy
{
    public function create(
        User $user,
        Assessment $assessment,
        StudentModuleRegistration $registration,
    ): bool
    {
        if (
            $registration->module_id !== $assessment->module_id
            || (int) $registration->semesterRegistration()->value('session_id') !== $assessment->session_id
        ) {
            return false;
        }

        if ($user->hasRole('registrar')) {
            return true;
        }

        return $user->hasRole('lecturer')
            && $user->lecturer()
                ->whereHas('teachingAllocations', fn ($query) => $query
                    ->where('module_id', $assessment->module_id)
                    ->where('session_id', $assessment->session_id))
                ->exists();
    }
}
