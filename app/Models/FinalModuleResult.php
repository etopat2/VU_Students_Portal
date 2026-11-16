<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinalModuleResult extends Model
{
    protected $fillable = [
        'student_module_registration_id',
        'calculated_by',
        'coursework_score',
        'exam_score',
        'final_mark',
        'letter_grade',
        'grade_point',
        'calculated_at',
    ];

    protected function casts(): array
    {
        return [
            'coursework_score' => 'decimal:2',
            'exam_score' => 'decimal:2',
            'final_mark' => 'decimal:2',
            'grade_point' => 'decimal:2',
            'calculated_at' => 'datetime',
        ];
    }

    public function studentModuleRegistration(): BelongsTo
    {
        return $this->belongsTo(StudentModuleRegistration::class);
    }

    public function calculatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'calculated_by');
    }
}
