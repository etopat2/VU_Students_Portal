<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Assessment extends Model
{
    protected $fillable = [
        'module_id',
        'session_id',
        'lecturer_id',
        'name',
        'assessment_type',
        'max_score',
        'weight_percentage',
        'due_date',
        'starts_at',
        'ends_at',
        'question_file_path',
        'question_file_original_name',
    ];

    protected function casts(): array
    {
        return [
            'max_score' => 'decimal:2',
            'weight_percentage' => 'decimal:2',
            'due_date' => 'date',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class);
    }

    public function lecturer(): BelongsTo
    {
        return $this->belongsTo(Lecturer::class);
    }

    public function results(): HasMany
    {
        return $this->hasMany(AssessmentResult::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(AssessmentSubmission::class);
    }

    public function submissionDeadline(): ?Carbon
    {
        if ($this->ends_at !== null) {
            return $this->ends_at;
        }

        return $this->due_date?->copy()->endOfDay();
    }

    public function hasStarted(): bool
    {
        return $this->starts_at === null || $this->starts_at->lessThanOrEqualTo(now());
    }

    public function submissionWindowIsOpen(): bool
    {
        $deadline = $this->submissionDeadline();

        return $this->hasStarted() && ($deadline === null || $deadline->greaterThanOrEqualTo(now()));
    }
}
