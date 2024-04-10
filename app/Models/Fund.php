<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Http\Request;

class Fund extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'fund_requests';

    protected $guarded = [];

    /**
     * Get the reviewer that owns the Fund
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the reviewer that user the Fund
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeAdminFiterByRequest($query, Request $request)
    {
        if (!empty($request['user_id'])) {
            $query->join('users', 'users.id', '=', 'fund_requests.user_id')
                ->where('users.phone_number', $request->user_id)
                ->select('fund_requests.*');
        }

        return $query;
    }
}
