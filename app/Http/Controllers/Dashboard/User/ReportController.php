<?php

namespace App\Http\Controllers\Dashboard\User;

use App\Exports\Dashboard\User\PayoutExport;
use App\Exports\Dashboard\User\TransactionExport;
use App\Http\Controllers\Controller;
use App\Http\Resources\GeneralResource;
use App\Models\Fund;
use App\Models\Payout;
use App\Models\Transaction;
use App\Models\WalletTransfer;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->transaction_id;
        if (!is_null($search) || !empty($search)) {
            $data = Transaction::where('user_id', $request->user()->id)
                ->whereAny(['refernce_id', 'id'], 'LIKE', "%$search%")
                ->paginate(30);
        } else {
            $data = Transaction::where('user_id', $request->user()->id)
                ->whereBetween('created_at', [$request->from ?? Carbon::now()->startOfDay(), $request->to ?? Carbon::now()->endOfDay()])
                ->paginate(30);
        }

        return GeneralResource::collection($data);
    }

    public function dailySales(Request  $request): JsonResource
    {
        $data = Transaction::where('user_id', $request->user()->id)->whereBetween('created_at', [$request->from ?? Carbon::now()->startOfDay(), $request->to ?? Carbon::now()->endOfDay()])->get();

        $transaction = $data->groupBy(['user_id', 'service'])->map(function ($item) {
            return $item->map(function ($key) {
                return ['debit_amount' => $key->sum('debit_amount'), 'credit_amount' => $key->sum('credit_amount')];
            });
        });

        return GeneralResource::collection($transaction);
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
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function fundRequests(Request $request): JsonResource
    {
        $data = Fund::where('user_id', $request->user()->id)
            ->whereBetween('created_at', [$request->from ?? Carbon::now()->startOfDay(), $request->to ?? Carbon::now()->endOfDay()])
            ->paginate(30);

        return GeneralResource::collection($data);
    }

    public function walletTransfers(Request $request): JsonResource
    {
        $data = WalletTransfer::where('user_id', $request->user()->id)
            ->whereBetween('created_at', [$request->from ?? Carbon::now()->startOfDay(), $request->to ?? Carbon::now()->endOfDay()])
            ->paginate(30);

        return GeneralResource::collection($data);
    }

    public function showFundRequests(Request $request, Fund $fund): JsonResource
    {
        $data = $fund->where('user_id', $request->user()->id)
            ->whereBetween('created_at', [$request->from ?? Carbon::now()->startOfDay(), $request->to ?? Carbon::now()->endOfDay()])
            ->with('reviewer')
            ->paginate(30);

        return GeneralResource::collection($data);
    }

    public function export(Request $request)
    {
        $request->validate(['format' => ['required', 'in:xlsx,pdf']]);
        switch ($request['report']) {
            case 'payouts':
                return Excel::download(new PayoutExport($request->from, $request->to), "payouts.{$request->format}");
                break;

            case 'transactions':
                return Excel::download(new TransactionExport($request->from, $request->to), "transactions.{$request->format}");
                break;

            default:
                return Excel::download(new TransactionExport($request->from, $request->to), "transactions.{$request->format}");
                break;
        }
    }
}
