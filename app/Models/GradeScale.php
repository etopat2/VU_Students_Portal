<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GradeScale extends Model
{
    protected $fillable = ['letter_grade', 'minimum_mark', 'grade_point'];

    protected function casts(): array
    {
        return ['minimum_mark' => 'decimal:2', 'grade_point' => 'decimal:2'];
    }
}
