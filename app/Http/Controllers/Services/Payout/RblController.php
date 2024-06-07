<?php

namespace App\Http\Controllers\Services\Payout;

use App\Http\Controllers\Controller;
use App\Http\Requests\PayoutRequest;
use Illuminate\Http\Request;

class RblController extends Controller
{
    public function initiateTransaction(PayoutRequest $request, string $reference_id): Response | Exception
    {
        $data = [
            'Single_Payment_Corp_Req' => [
                'Header' => [
                    'TranID' => '',
                    'Corp_ID' => '',
                    'Maker_ID' => '',
                    'Checker_ID' => '',
                    'Approver_ID' => '',
                ],

                'Body' => [
                    
                ]
            ]
        ];
    }
}
