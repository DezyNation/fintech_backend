<?php

namespace App\Models;

use App\Http\Resources\GeneralResource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Http\Request;

class Transaction extends Model
{
    use HasFactory, HasUuids;

    protected $fillable  = [
        'user_id',
        'updated_by',
        'triggered_by',
        'reference_id',
        'service',
        'description',
        'credit_amount',
        'debit_amount',
        'opening_balance',
        'closing_balance',
        'metadata'
    ];

    /**
     * Get the beneficiary that owns the Transaction
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function beneficiary(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the reviewer that owns the Transaction
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the reviewer that owns the Transaction
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function triggered_by(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }

    public static function dailySales($query)
    {
        $result = $query->join('users', 'transactions.user_id', '=', 'users.id')
            ->select(
                'user_id',
                'service'
            )
            ->selectRaw('SUM(credit_amount) as credit')
            ->selectRaw('SUM(debit_amount) as debit')
            ->groupBy('user_id', 'service')
            ->get();

        $formattedResult = [];

        foreach ($result as $item) {
            $user_id = $item->user_id;

            if (!isset($formattedResult[$user_id])) {
                $formattedResult[$user_id] = [
                    'user_id' => $user_id,
                    'services' => [],
                ];
            }

            $formattedResult[$user_id]['services'][] = [
                'service_type' => $item->service_type,
                'total_credit_amount' => $item->total_credit_amount,
                'total_debit_amount' => $item->total_debit_amount,
            ];
        }

        return GeneralResource::collection($formattedResult);
    }

    public function scopeAdminFiterByRequest($query, Request $request)
    {
        if (!empty($request['transaction_id'])) {
            $query->where('reference_id', 'like', "%{$request->transaction_id}%");
        }


        if (!empty($request['user_id'])) {
            $query->join('users', 'users.id', '=', 'payouts.user_id')
                ->join('users as reviewer', 'users.id', '=', 'payouts.user_id')
                ->join('users as initiator', 'users.id', '=', 'payouts.user_id')
                ->where(function ($q) use ($request) {
                    $q->where('users.phone_number', $request->user_id)
                        ->orWhere('reviewer.phone_number', $request->user_id)
                        ->orWhere('initiator.phone_number', $request->user_id);
                });
        }

        return $query;
    }
}
