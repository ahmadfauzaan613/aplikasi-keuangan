<?php

namespace App\Actions;

use App\Models\Debt;
use Illuminate\Support\Facades\DB;

class ToggleDebtStatusAction
{
    /**
     * Toggle the status of a debt (paid vs unpaid).
     */
    public function execute(Debt $debt): Debt
    {
        return DB::transaction(function () use ($debt) {
            $debt->update([
                'status' => $debt->status === 'paid' ? 'unpaid' : 'paid'
            ]);
            return $debt;
        });
    }
}
