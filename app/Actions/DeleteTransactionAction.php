<?php

namespace App\Actions;

use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class DeleteTransactionAction
{
    /**
     * Execute the action to delete a transaction.
     */
    public function execute(Transaction $transaction): bool
    {
        return DB::transaction(function () use ($transaction) {
            return $transaction->delete();
        });
    }
}
