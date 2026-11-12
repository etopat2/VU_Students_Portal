<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lecturer extends Model
{
    protected $fillable = [
        'lecturer_number',
        'name',
        'email',
        'department',
        'qualification',
        'phone',
        'status',
    ];
}

