<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudySessionChangeRequest extends Model
{
    protected $fillable = [
        'student_id',
        'requested_by',
        'current_study_session_id',
        'requested_study_session_id',
        'current_study_program_id',
        'requested_study_program_id',
        'reason',
        'status',
        'reviewed_by',
        'review_notes',
        'reviewed_at',
        'is_strategic',
    ];

    protected function casts(): array
    {
        return ['reviewed_at' => 'datetime', 'is_strategic' => 'boolean'];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function currentStudySession(): BelongsTo
    {
        return $this->belongsTo(StudySession::class, 'current_study_session_id');
    }

    public function requestedStudySession(): BelongsTo
    {
        return $this->belongsTo(StudySession::class, 'requested_study_session_id');
    }

    public function currentStudyProgram(): BelongsTo
    {
        return $this->belongsTo(StudyProgram::class, 'current_study_program_id');
    }

    public function requestedStudyProgram(): BelongsTo
    {
        return $this->belongsTo(StudyProgram::class, 'requested_study_program_id');
    }
}
