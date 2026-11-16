<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Support\Money;

class Invoice extends Model
{
    protected $fillable = [
        'student_id',
        'session_id',
        'semester_registration_id',
        'issued_by',
        'invoice_number',
        'invoice_type',
        'description',
        'line_items',
        'amount',
        'currency',
        'status',
        'due_date',
    ];

    protected function casts(): array
    {
        return [
            'line_items' => 'array',
            'amount' => 'decimal:2',
            'due_date' => 'date',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class);
    }

    public function semesterRegistration(): BelongsTo
    {
        return $this->belongsTo(SemesterRegistration::class);
    }

    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function ledgerEntry(): HasOne
    {
        return $this->hasOne(FinancialLedger::class);
    }

    public function verifiedPaidAmountInMinorUnits(): int
    {
        $payments = $this->relationLoaded('payments')
            ? $this->payments->where('status', 'verified')
            : $this->payments()->where('status', 'verified')->get(['amount']);

        return $payments->reduce(
            fn (int $total, Payment $payment): int => $total + Money::minorUnits((string) $payment->amount),
            0,
        );
    }

    public function outstandingAmountInMinorUnits(): int
    {
        return Money::minorUnits((string) $this->amount) - $this->verifiedPaidAmountInMinorUnits();
    }
}
