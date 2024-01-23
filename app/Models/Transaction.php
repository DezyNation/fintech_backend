<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
}
