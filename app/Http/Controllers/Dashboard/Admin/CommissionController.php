<?php

namespace App\Http\Controllers\Dashboard\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CommissionRequest;
use App\Http\Resources\GeneralResource;
use App\Models\PayoutCommission;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommissionController extends Controller
{
    public function updatePayoutCommission(Request $request, PayoutCommission $payout): JsonResource
    {
        $payout->update([
            'plan_id' => $request->planId ?? $payout->plan_id,
            'role_id' => $request->roleId ?? $payout->role_id,
            'from' => $request->from ?? $payout->from,
            'to' => $request->to ?? $payout->to,
            'fixed_charge' => $request->fixedCharge ?? $payout->fixed_charge,
            'commission' => $request->commission ?? $payout->commission,
            'is_flat' => $request->isFlat ?? $payout->is_flat
        ]);

        return new GeneralResource($payout);
    }

    public function createPayoutCommission(CommissionRequest $request): JsonResource
    {
        $data = PayoutCommission::create([
            'plan_id' => $request->planId,
            'role_id' => $request->roleId,
            'from' => $request->from,
            'to' => $request->to,
            'fixed_charge' => $request->fixedCharge,
            'commission' => $request->commission,
            'is_flat' => $request->isFlat,
        ]);

        return new GeneralResource($data);
    }

    public function createCommission(Request $request): JsonResource
    {
        $request->validate([
            'service' => ['required', 'in:payout,aeps']
        ]);
        $service = $request->service;
        switch ($service) {
            case 'payout':
                $data = $this->createPayoutCommission($request);
                break;

            default:
                $data = new GeneralResource('no data sent');
                break;
        }

        return $data;
    }
}