<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudySession extends Model
{
    protected $fillable = ['code', 'name', 'audience', 'description', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function changeRequests(): HasMany
    {
        return $this->hasMany(StudySessionChangeRequest::class, 'requested_study_session_id');
    }
}
