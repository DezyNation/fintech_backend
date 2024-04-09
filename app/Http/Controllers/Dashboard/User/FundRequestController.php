<?php

namespace App\Http\Controllers\Dashboard\User;

use App\Models\Fund;
use Illuminate\Http\Request;
use App\Models\WalletTransfer;
use App\Http\Requests\FundRequest;
use App\Http\Controllers\Controller;
use App\Http\Controllers\TransactionController;
use App\Http\Resources\GeneralResource;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;

class FundRequestController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $data = Fund::where('user_id', $request->user()->id)->paginate(30);
        return GeneralResource::collection($data);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(FundRequest $request)
    {
        $data = Fund::create([
            'user_id' => $request->user()->id,
            'transaction_id' => $request->transaction_id,
            'transaction_date' => $request->transaction_date,
            'amount' => $request->amount,
            'opening_balance' => $request->user()->wallet ?? 0,
            'closing_balance' => $request->user()->wallet ?? 0,
            'user_remarks' => $request->user_remarks
        ]);

        return new GeneralResource($data);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {
        $data = Fund::where(['user_id' => $request->user()->id, 'id' => $id])->get();
        return new GeneralResource($data);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function walletTransfer(Request $request): JsonResource
    {
        $request->validate(
            [
                'amount' => ['required', 'min:1', 'numeric'],
                'receiver_id' => ['required', 'exists:users,id']
            ]

        );

        if ($request->receiver_id == $request->user()->id) {
            abort(400, "You can not transfer money to yourself.");
        }

        $receiver = User::findOrFail($request->receiver_id);
        $user = $request->user();
        $reference_id = uniqid("WT");

        $data = WalletTransfer::create([
            'from' => $user->id,
            'to' => $request->receiver_id,
            'reference_id' => $reference_id,
            'user_remarks' => $request->user_remarks,
            'status' => 'success',
            'amount' => $request->amount,
            'approved_by' => $user->id
        ]);

        TransactionController::store($user, $reference_id, 'wallet_transfer', "Mony Transfer to {$receiver->name}", 0, $request->amount);
        TransactionController::store($receiver, $reference_id, 'wallet_transfer', "Mony received from {$user->name}", $request->amount, 0);

        return new GeneralResource($data);
    }
}
