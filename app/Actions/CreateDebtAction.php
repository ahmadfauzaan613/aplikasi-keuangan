<?php

namespace App\Actions;

use App\Models\Debt;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateDebtAction
{
    /**
     * Execute the action to create a debt/receivable.
     *
     * @param array{
     *     name: string,
     *     type: string,
     *     amount: float|numeric,
     *     due_date?: string|null,
     *     description?: string|null
     * } $data
     */
    public function execute(User $user, array $data): Debt
    {
        return DB::transaction(function () use ($user, $data) {
            return $user->debts()->create([
                'name' => $data['name'],
                'type' => $data['type'],
                'amount' => $data['amount'],
                'due_date' => $data['due_date'] ?? null,
                'status' => 'unpaid',
                'description' => $data['description'] ?? null,
            ]);
        });
    }
}
