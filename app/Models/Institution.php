<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Institution extends Model
{
    protected $fillable = ['name', 'code', 'email', 'phone', 'address'];

    public function faculties(): HasMany
    {
        return $this->hasMany(Faculty::class);
    }
}
