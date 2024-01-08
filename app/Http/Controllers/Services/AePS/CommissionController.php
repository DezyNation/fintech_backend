<?php

namespace App\Http\Controllers\Services\AePS;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CommissionController extends Controller
{
    public function distributeCommission(User $user, float $amount): Collection
    {
        $role_id = $user->getRoleId();
        $plan_id = $user->plan_id;
        $commission = DB::table('aeps_commissions')->where(['plan_id' => $plan_id, 'role_id' => $role_id])->where('from', '=<', $amount)->where('to', '>', $amount)->first();

        $fixed_charge = $commission->fixed_charge;
        if ($commission->is_flat) {
            $credit = $commission->commission;
        } else {
            $credit = $amount - ($amount*$commission->commission)/100;
        }

        return $commission;
    }
}
