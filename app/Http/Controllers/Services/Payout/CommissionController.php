<?php

namespace App\Http\Controllers\Services\Payout;

use App\Http\Controllers\Controller;
use App\Http\Controllers\TransactionController;
use App\Models\PayoutCommission;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class CommissionController extends Controller
{
    public function findCommission(User $user): array
    {
        return [
            'plan_id' => $user->plan_id,
            'role_id' => $user->getRoleId(),
        ];
    }

    public function distributeCommission(User $user, float $amount, string $reference_id, bool $parent = false, bool $calculation = false): Model | array 
    {
        $instance = PayoutCommission::where($this->findCommission($user))->where('from', '<', $amount)->where('to', '>=', $amount)->get()->first();

        if (!$instance) {[
            'debit_amount' => $fixed_charge = 0,
            'credit_amount' => $credit = 0
        ];
        }

        $fixed_charge = $parent ? 0 : ($instance->fixed_charge_flat ? $instance->fixed_charge_flat : $amount * $instance->fixed_charge_flat / 100);
        $credit = $instance->is_flat ? $instance->commission : $amount * $instance->commission / 100;
        if ($calculation == true) {
            return [
                'debit_amount' => $fixed_charge,
                'credit_amount' => $credit
            ];
        }
        TransactionController::store($user, $reference_id, 'payout_commission', "Payout Commission", $credit, $fixed_charge);
        $this->checkParent($user, $amount);
        return $instance;
    }

    public function checkParent(User $user, float $amount)
    {
        if (!is_null($user->parent_id)) {
            $parent = User::find($user->parent_id);
            $lock = $this->lockRecords($user->parent_id);
            $this->distributeCommission($parent, $amount, true);
            $lock->release();
        }
    }
}
