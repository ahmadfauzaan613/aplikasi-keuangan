<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class Transaction extends Model
{
    use HasFactory, HasUlids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'amount',
        'type',
        'category',
        'description',
        'transaction_date',
        'metadata',
        'user_id',
        'status',
        'due_date',
        'paid_amount',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'transaction_date' => 'date',
            'due_date' => 'date',
            'paid_amount' => 'decimal:2',
            'metadata' => 'array',
        ];
    }

    /**
     * Get the user that owns the transaction.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to only include income transactions.
     */
    public function scopeIncome(Builder $query): Builder
    {
        return $query->where('type', 'income');
    }

    /**
     * Scope a query to only include expense transactions.
     */
    public function scopeExpense(Builder $query): Builder
    {
        return $query->where('type', 'expense');
    }

    /**
     * Scope a query to only include paid transactions.
     */
    public function scopePaid(Builder $query): Builder
    {
        return $query->where('status', 'sudah');
    }

    /**
     * Scope a query to only include unpaid transactions.
     */
    public function scopeUnpaid(Builder $query): Builder
    {
        return $query->where('status', 'belum');
    }

    /**
     * Scope a query to filter by date range.
     */
    public function scopeBetweenDates(Builder $query, $startDate, $endDate): Builder
    {
        return $query->whereBetween('transaction_date', [$startDate, $endDate]);
    }
}
