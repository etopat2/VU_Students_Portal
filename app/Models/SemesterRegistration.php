<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;

class SemesterRegistration extends Model
{
    protected $fillable = [
        'student_id',
        'session_id',
        'enrollment_id',
        'registered_by',
        'status',
        'registered_at',
    ];

    protected function casts(): array
    {
        return ['registered_at' => 'datetime'];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class);
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function registeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by');
    }

    public function moduleRegistrations(): HasMany
    {
        return $this->hasMany(StudentModuleRegistration::class);
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    public function semesterGpa(): ?float
    {
        $totals = DB::table('student_module_registrations')
            ->join(
                'final_module_results',
                'final_module_results.student_module_registration_id',
                '=',
                'student_module_registrations.id',
            )
            ->join('modules', 'modules.id', '=', 'student_module_registrations.module_id')
            ->where('student_module_registrations.semester_registration_id', $this->getKey())
            ->selectRaw(
                'SUM(final_module_results.grade_point * modules.credit_units) AS weighted_points, '
                .'SUM(modules.credit_units) AS credit_units',
            )
            ->first();

        if (! $totals || (int) $totals->credit_units === 0) {
            return null;
        }

        return round((float) $totals->weighted_points / (int) $totals->credit_units, 2);
    }
}
