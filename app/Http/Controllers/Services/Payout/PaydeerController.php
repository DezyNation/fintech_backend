<?php

namespace App\Http\Controllers\Services\Payout;

use App\Http\Controllers\Controller;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class PaydeerController extends Controller
{
    public function initiateTransaction(Request $request): Response
    {
        if (!Cache::has('paydeer-token')) {
            $token = $this->paydeerToken();
            Cache::put('paydeer-token', $token['data']['access_token']);
        }

        $token = Cache::get('paydeer-token');

        $data = [
            'name' => $request->beneficiary_name,
            'email' => $request->user()->email,
            'mobile' => $request->user()->phone_number,
            'address' => $request->user()->address ?? 'Dubai',
            'amount' => $request->amount,
            'reference' => uniqid('PAYO-PD'),
            'trans_mode' => $request->mode,
            'account' => $request->account_number,
            'ifsc' => $request->ifsc_code
        ];
        $response = Http::withHeader('Authorization', 'Bearer ' . $token)
            ->post('https://paydeer.in/API/public/api/v1.1/payoutTransaction/async', $data);
        return $response;
    }
}
