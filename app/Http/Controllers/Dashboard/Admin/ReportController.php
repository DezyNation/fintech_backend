<?php

namespace App\Http\Controllers\Dashboard\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\GeneralResource;
use App\Models\Payout;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReportController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $data = Transaction::with(['beneficiary', 'reviewer', 'triggered_by'])
            ->whereBetween('created_at', [$request->start, $request->end])->paginate(30);

        return new GeneralResource($data);
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
    public function show(Request $request, string $id)
    {
        $data = Transaction::with(['beneficiary', 'reviewer', 'triggered_by'])
            ->whereBetween('created_at', [$request->start, $request->end])
            ->where(function ($q) use ($id) {
                $q->where('user_id', $id)
                    ->orWhere('triggered_by', $id)
                    ->orWhere('updated_by', $id);
            })
            ->paginate(30);

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

    public function dailySales(): JsonResource
    {
        $data = Transaction::whereBetween('created_at', [Carbon::now()->startOfDay(), Carbon::now()->endOfDay()])->get();

        $transaction = $data->groupBy(['user_id', 'service'])->map(function ($item) {
            return $item->map(function ($key) {
                return ['debit_amount' => $key->sum('debit_amount'), 'credit_amount' => $key->sum('credit_amount')];
            });
        });

        return new GeneralResource($transaction);
    }

    public function payoutReports(Request $request): JsonResource
    {
        $data = Payout::with('user')->whereBetween('created_at', [$request->from ?? Carbon::now()->startOfWeek(), $request->to ?? Carbon::now()->endOfDay()])
            ->paginate(30);
        return new GeneralResource($data);
    }
}
