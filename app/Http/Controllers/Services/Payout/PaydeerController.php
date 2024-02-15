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
        $request->validate([
            'name' => ['required', 'string'],
            'email' => ['required', 'email'],
            'mobile' => ['required', 'digits:10'],
            'address' => ['nullable', 'string'],
            'amount' => ['required', 'numeric', 'min:1'],
            'txnType' => ['required'],
            'account' => ['required', 'between:11,16'],
            'ifsc' => ['required', 'string'],
        ]);

        if (!Cache::has('paydeer-token')) {
            $token = $this->paydeerToken();
            Cache::put('paydeer-token', $token['data']['access_token']);
        }

        $token = Cache::get('paydeer-token');

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'mobile' => $request->mobile,
            'address' => $request->address,
            'amount' => $request->amount,
            'reference' => uniqid('PAYO-PD'),
            'trans_mode' => $request->txnType,
            'account' => $request->account,
            'ifsc' => $request->ifsc
        ];
        $response = Http::withHeader('Authorization', 'Bearer ' . $token)
            ->post('https://paydeer.in/API/public/api/v1.1/payoutTransaction/async', $data);
        return $response;
    }
}
