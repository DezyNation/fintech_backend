<?php

namespace App\Http\Controllers\Dashboard\Admin;

use App\Exports\Dashboard\Admin\FundRequestExport;
use Carbon\Carbon;
use App\Models\Payout;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Resources\GeneralResource;
use App\Exports\Dashboard\Admin\PayoutExport;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Exports\Dashboard\Admin\TransactionExport;
use App\Models\Fund;
use App\Models\FundTransfer;
use App\Models\WalletTransfer;

class ReportController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {

        $data = Transaction::with(['beneficiary', 'reviewer', 'triggered_by'])
            ->adminFiterByRequest($request)
            ->whereBetween('transactions.created_at', [$request->from, $request->to])
            ->paginate(30);

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

        return GeneralResource::collection($data);
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

        return GeneralResource::collection($transaction);
    }

    public function walletTransferReport(Request $request): JsonResource
    {
        $data = WalletTransfer::adminFiterByRequest($request)
            ->with(['sender', 'receiver'])
            ->whereBetween('wallet_transfers.created_at', [$request->from ?? Carbon::now()->startOfDay(), $request->to ?? Carbon::now()->endOfDay()])
            ->paginate(30);
        return GeneralResource::collection($data);
    }

    public function fundRequestReport(Request $request): JsonResource
    {
        $data = Fund::adminFiterByRequest($request)->with(['reviewer' => function ($q) {
            $q->select('id', 'name');
        }, 'user' => function ($q) {
            $q->select('id', 'name');
        }])->whereBetween('fund_requests.created_at', [$request->from ?? Carbon::now()->startOfDay(), $request->to ?? Carbon::now()->endOfDay()])->paginate(30);
        return GeneralResource::collection($data);
    }

    public function payoutReports(Request $request): JsonResource
    {
        $data = Payout::with('user')
            ->adminFilterByRequest($request)
            ->whereBetween('created_at', [$request->from ?? Carbon::now()->startOfWeek(), $request->to ?? Carbon::now()->endOfDay()])
            ->paginate(30);
        return GeneralResource::collection($data);
    }

    public function fundTransferReport(Request $request)
    {
        $data = FundTransfer::filterByRequest($request)
            ->with(['user' => function ($q) {
                $q->select('id', 'name');
            }, 'admin' => function ($q) {
                $q->select('id', 'name');
            }])->whereBetween('created_at', [$request->from ?? Carbon::now()->startOfWeek(), $request->to ?? Carbon::now()->endOfDay()])
            ->paginate(30);

        return GeneralResource::collection($data);
    }

    public function export(Request $request)
    {
        $request->validate([
            // 'user_id' => ['required', 'exists:users,id'],
            'format' => ['required', 'in:xlsx,pdf']
        ]);
        switch ($request['report']) {
            case 'payouts':
                return Excel::download(new PayoutExport($request->from, $request->to, $request->user_id), "payouts.{$request->format}");
                break;

            case 'transactions':
                return Excel::download(new TransactionExport($request->from, $request->to, $request->user_id), "transactions.{$request->format}");
                break;

            case 'fund-requests':
                return Excel::download(new FundRequestExport($request->from, $request->to, $request), "fund_requests.{$request->format}");
                break;

            default:
                return Excel::download(new TransactionExport($request->from, $request->to, $request->user_id), "transactions.{$request->format}");
                break;
        }
    }
}
