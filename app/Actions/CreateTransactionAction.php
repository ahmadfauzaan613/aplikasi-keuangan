<?php

namespace App\Actions;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateTransactionAction
{
    /**
     * Execute the action to create a transaction.
     *
     * @param array{
     *     title: string,
     *     amount: float|numeric,
     *     type: string,
     *     category: string,
     *     description?: string|null,
     *     transaction_date: string,
     *     metadata?: array|null,
     *     status?: string,
     *     due_date?: string|null,
     *     paid_amount?: float|numeric
     * } $data
     */
    public function execute(User $user, array $data): Transaction
    {
        return DB::transaction(function () use ($user, $data) {
            return $user->transactions()->create([
                'title' => $data['title'],
                'amount' => $data['amount'],
                'type' => $data['type'],
                'category' => $data['category'],
                'description' => $data['description'] ?? null,
                'transaction_date' => $data['transaction_date'],
                'metadata' => $data['metadata'] ?? [],
                'status' => $data['status'] ?? 'sudah',
                'due_date' => $data['due_date'] ?? null,
                'paid_amount' => $data['paid_amount'] ?? 0.00,
            ]);
        });
    }
}
