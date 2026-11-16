<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssessmentSubmission extends Model
{
    protected $fillable = [
        'assessment_id',
        'student_module_registration_id',
        'answer_content',
        'answer_file_path',
        'answer_file_original_name',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return ['submitted_at' => 'datetime'];
    }

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class);
    }

    public function studentModuleRegistration(): BelongsTo
    {
        return $this->belongsTo(StudentModuleRegistration::class);
    }
}
