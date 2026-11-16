<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashRegister extends Model
{
    protected $fillable = [
        'opened_by',
        'closed_by',
        'register_date',
        'opening_balance',
        'expected_balance',
        'closing_balance',
        'variance',
        'status',
        'opened_at',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'register_date' => 'date',
            'opening_balance' => 'decimal:2',
            'expected_balance' => 'decimal:2',
            'closing_balance' => 'decimal:2',
            'variance' => 'decimal:2',
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function openedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(CashTransaction::class);
    }

    public function expectedBalanceInMinorUnits(): int
    {
        $balance = \App\Support\Money::minorUnits((string) $this->opening_balance);

        foreach ($this->transactions()->get(['amount', 'transaction_type']) as $transaction) {
            $amount = \App\Support\Money::minorUnits((string) $transaction->amount);
            $balance += $transaction->transaction_type === 'collection' ? $amount : -$amount;
        }

        return $balance;
    }
}
