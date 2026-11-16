<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashTransaction extends Model
{
    protected $fillable = [
        'cash_register_id',
        'payment_id',
        'expense_id',
        'recorded_by',
        'transaction_type',
        'receipt_number',
        'amount',
        'description',
        'posted_at',
    ];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'posted_at' => 'datetime'];
    }

    public function cashRegister(): BelongsTo
    {
        return $this->belongsTo(CashRegister::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
