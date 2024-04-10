<?php

namespace App\Http\Controllers\Dashboard\Admin;

use App\Models\Fund;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Resources\GeneralResource;
use App\Http\Controllers\TransactionController;
use App\Models\FundTransfer;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Resources\Json\JsonResource;

class FundRequestController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $data = Fund::with(['user' => function ($q) {
            $q->select('id', 'name');
        }, 'reviewer' => function ($q) {
            $q->select('id', 'name');
        }])->where('status', $request->status)->paginate(20);
        return GeneralResource::collection($data);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'status' => ['nullable', 'in:approved,rejected'],
            'admin_remarks' => ['nullable', 'required_if:status,rejected']
        ]);

        $fund = Fund::where(['id' => $id, 'status' => 'pending'])->lockForUpdate()->first();
        $fund_lock = $this->lockRecords($fund->token);

        if (!$fund_lock->get()) {
            abort(423, "Can't lock the fund request at the moment.");
        }

        $data = DB::transaction(function () use ($request, $fund, $fund_lock) {
            $user_lock = $this->lockRecords($fund->user_id);

            if (!$user_lock->get()) {
                abort(423, "Can't lock the user at the moment.");
            }

            $user = User::where('user_id', $fund->user_id)->findOrFail($fund->user_id);
            return $user;
            if ($request->status == 'approved') {
                TransactionController::store($user, $fund->transaction_id, 'fund-request', 'Fund Request approved.', $fund->amount, 0);
            }
            $fund->status = $request->status;
            $fund->admin_remarks = $request->admin_remarks;
            $fund->updated_by = $request->user()->id;
            $fund->save();
            $user_lock->release();
            $fund_lock->release();
        }, 2);

        return new GeneralResource($data);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function assignRequest(Request $request, string $id)
    {
        $request->validate([
            'token' => ['required', 'uuid'],
            'assign_to' => ['required', 'exists:users,id']
        ]);

        $fund = Fund::where(['id' => $id, 'token' => $request->token, 'status' => 'pending'])->first();
        $fund_lock = $this->lockRecords($fund->token);
        if (!$fund_lock->get()) {
            throw new HttpResponseException(response()->json(['message' => "Failed to acquire lock"], 423));
        }
        DB::transaction(function () use ($request, $fund, $fund_lock) {
            $fund->assigned_to = $request->assign_to;
            $fund->save();
            $fund_lock->release();
        });

        return new GeneralResource($fund);
    }

    public function fundTransfer(Request $request, User $user): JsonResource
    {
        $request->validate([
            'activity' => ['required', 'in:transfer,reversal'],
            'amount' => ['required', 'numeric', 'min:1'],
            'remarks' => ['required', 'string']
        ]);

        $transfer = DB::transaction(function () use ($request, $user) {

            $lock = $this->lockRecords($user->id);
            if (!$lock->get()) {
                abort(423, "Can't lock the user at the moment.");
            }

            if ($request->activity == 'transfer') {
                $opening_balance = $user->wallet;
                $closing_balance = $user->wallet + $request->amount;
                $reference_id = Str::random(8);
                TransactionController::store($user, $reference_id, 'fund-request', 'Fund Request approved.', $request->amount, 0);
            } else {
                $opening_balance = $user->wallet;
                $closing_balance = $user->wallet - $request->amount;
                $reference_id = Str::random(8);
                TransactionController::store($user, $reference_id, 'fund-request', 'Fund Request approved.', 0, $request->amount);
            }

            $data = FundTransfer::create([
                'user_id' => $user->id,
                'admin_id' => $request->user()->id,
                'activity' => $request->activity,
                'reference_id' => $reference_id,
                'amount' => $request->amount,
                'opening_balance' => $opening_balance,
                'closing_balance' => $closing_balance,
                'remarks' => $request->remarks,
            ]);
            $lock->release();
            return new GeneralResource($data);
        });

        return $transfer;
    }
}
