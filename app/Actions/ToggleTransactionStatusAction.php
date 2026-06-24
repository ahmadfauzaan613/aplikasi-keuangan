<?php

namespace App\Actions;

use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class ToggleTransactionStatusAction
{
    /**
     * Toggle the status of a transaction (sudah vs belum).
     */
    public function execute(Transaction $transaction): Transaction
    {
        return DB::transaction(function () use ($transaction) {
            $transaction->update([
                'status' => $transaction->status === 'sudah' ? 'belum' : 'sudah'
            ]);
            return $transaction;
        });
    }
}
