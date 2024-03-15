<?php

namespace App\Http\Controllers\Services\Payout;

use App\Http\Controllers\Controller;
use App\Models\PayoutCommission;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class CommissionController extends Controller
{
    public function findCommission(User $user, string $service): array
    {
        return [
            'plan_id' => $user->plan_id,
            'role_id' => $user->getRoleId(),
            'service' => $service
        ];
    }

    public function distributeCommission(User $user, string $service, float $amount, bool $parent = false): Model
    {
        $instance = PayoutCommission::where([$this->findCommission($user, $service)])->where('from', '<', $amount)->where('to', '>=', $amount)->get()->first();
        $fixed_charge = $parent ? 0 : $instance->fixed_charge;
        $credit = $instance->is_flat ? $instance->commission : $amount * $instance->commission / 100;
        $this->checkParent($user, $service, $amount);
        return $instance;
    }

    public function checkParent(User $user, string $service, float $amount)
    {
        if (!is_null($user->parent_id)) {
            $parent = User::find($user->parent_id);
            $this->distributeCommission($parent, $service, $amount, true);
        }
    }
}
