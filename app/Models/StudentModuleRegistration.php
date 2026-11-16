<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class StudentModuleRegistration extends Model
{
    protected $fillable = ['semester_registration_id', 'module_id', 'status'];

    public function semesterRegistration(): BelongsTo
    {
        return $this->belongsTo(SemesterRegistration::class);
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function assessmentResults(): HasMany
    {
        return $this->hasMany(AssessmentResult::class);
    }

    public function assessmentSubmissions(): HasMany
    {
        return $this->hasMany(AssessmentSubmission::class);
    }

    public function finalResult(): HasOne
    {
        return $this->hasOne(FinalModuleResult::class);
    }
}
