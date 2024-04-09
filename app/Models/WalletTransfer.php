<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\Request;

class WalletTransfer extends Model
{
    use HasFactory;

    protected $guarded = [];

    /**
     * Get the sender that owns the WalletTransfer
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id')->select('id', 'name');
    }

    /**
     * Get the sender that receiver the WalletTransfer
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_id')->select('id', 'name');
    }

    public function scopeAdminFiterByRequest($query, Request $request)
    {

        if (!empty($request['sender_id'])) {
            $query->join('users', 'users.id', '=', 'payouts.user_id')
                ->where('users.phone_number', $request->user_id);
        }

        if (!empty($request['receiver_id'])) {
            $query->join('users', 'users.id', '=', 'payouts.user_id')
                ->where('users.phone_number', $request->user_id);
        }

        return $query;
    }
}
