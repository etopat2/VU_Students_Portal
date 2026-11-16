<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssessmentResult extends Model
{
    protected $fillable = [
        'student_module_registration_id',
        'assessment_id',
        'entered_by',
        'score',
        'feedback',
        'entered_at',
    ];

    protected function casts(): array
    {
        return ['score' => 'decimal:2', 'entered_at' => 'datetime'];
    }

    public function studentModuleRegistration(): BelongsTo
    {
        return $this->belongsTo(StudentModuleRegistration::class);
    }

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class);
    }

    public function enteredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'entered_by');
    }
}
