<?php

namespace App\Policies;

use App\Models\User;

class PaymentPolicy
{
    public function record(User $user): bool
    {
        return $user->hasRole('accountant');
    }

    public function verify(User $user): bool
    {
        return $user->hasRole('accountant');
    }
}
