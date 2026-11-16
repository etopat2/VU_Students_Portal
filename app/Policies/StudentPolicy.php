<?php

namespace App\Policies;

use App\Models\Session;
use App\Models\Student;
use App\Models\User;

class StudentPolicy
{
    public function register(User $user, Student $student): bool
    {
        return $user->hasRole('registrar')
            || ($user->hasRole('student') && $student->user_id === $user->id);
    }

    public function viewExamPermit(User $user, Student $student, Session $session): bool
    {
        $staffCanView = $user->hasRole('registrar');
        $studentCanView = $user->hasRole('student')
            && $student->user_id === $user->id
            && $student->isFinanciallyCleared()
            && $student->semesterRegistrations()->where('session_id', $session->id)->exists();

        return $staffCanView || $studentCanView;
    }

    public function viewResults(User $user, Student $student): bool
    {
        return $user->hasRole('registrar')
            || (
                $user->hasRole('student')
                && $student->user_id === $user->id
                && $student->isFinanciallyCleared()
            );
    }

    public function viewFinancials(User $user, Student $student): bool
    {
        return $user->hasAnyRole(['registrar', 'accountant'])
            || ($user->hasRole('student') && $student->user_id === $user->id);
    }
}
