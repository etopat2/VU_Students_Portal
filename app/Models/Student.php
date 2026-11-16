<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Support\Money;
use Illuminate\Support\Facades\DB;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_number',
        'name',
        'email',
        'programme',
        'year',
        'status',
        'is_in_service_teacher',
        'course_id',
        'study_program_id',
        'study_session_id',
        'academic_year_id',
        'clearance_status',
    ];

    public function grades(): HasMany
    {
        return $this->hasMany(Grade::class);
    }

    protected function casts(): array
    {
        return ['is_in_service_teacher' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class)->withTimestamps();
    }

    public function primaryCourse(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    public function studyProgram(): BelongsTo
    {
        return $this->belongsTo(StudyProgram::class);
    }

    public function studySession(): BelongsTo
    {
        return $this->belongsTo(StudySession::class);
    }

    public function studySessionChangeRequests(): HasMany
    {
        return $this->hasMany(StudySessionChangeRequest::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function semesterRegistrations(): HasMany
    {
        return $this->hasMany(SemesterRegistration::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function financialLedger(): HasMany
    {
        return $this->hasMany(FinancialLedger::class);
    }

    public function latestFinancialLedger(): HasOne
    {
        return $this->hasOne(FinancialLedger::class)->latestOfMany();
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function currentLedgerBalance(): string
    {
        $balance = DB::table('financial_ledgers')
            ->where('student_id', $this->getKey())
            ->orderByDesc('id')
            ->value('balance_after');

        return $balance === null ? '0.00' : (string) $balance;
    }

    public function isFinanciallyCleared(): bool
    {
        return Money::minorUnits($this->currentLedgerBalance()) <= 0;
    }
}
