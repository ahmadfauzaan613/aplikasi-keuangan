<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class Allowance extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'name',
        'amount',
        'allowance_date',
        'status',
        'description',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'allowance_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeSudah(Builder $query): Builder
    {
        return $query->where('status', 'sudah');
    }

    public function scopeBelum(Builder $query): Builder
    {
        return $query->where('status', 'belum');
    }
}
