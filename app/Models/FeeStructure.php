<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeeStructure extends Model
{
    protected $fillable = [
        'course_id',
        'academic_year_id',
        'session_id',
        'tuition_amount',
        'functional_fees',
        'currency',
    ];

    protected function casts(): array
    {
        return ['tuition_amount' => 'decimal:2', 'functional_fees' => 'decimal:2'];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class);
    }
}
