<?php

namespace App\Http\Controllers\Services\Payout;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\PayoutRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class EkoController extends Controller
{

    public function processResponse(Response $response, string $provider): array
    {
        switch ($provider) {
            case 'eko':
                if ($response['status'] == 1) {
                    $data = [
                        'status' => 'success',
                        'message' => $response['message'],
                        'transaction_status' => $response['data']['txstatus_desc']
                    ];
                } else {
                    $data = [
                        'status' => 'error',
                        'message' => $response['message'] ?? "An error occurred while processing your request",
                    ];
                }
                break;

            default:
                $data = [
                    'status' => 'error',
                    'message' => $response['message'] ?? "An error occurred while processing your request",
                ];
                break;
        }

        return $data;
    }

    public function initiateTransaction(PayoutRequest $request, string $reference_id): array
    {
        $data = [
            'recipient_id' => $request->recipient_id,
            'amount' => $request->amount * 100,
            'timestamp' => now(),
            'currency' => 'INR',
            'customer_id' => $request->phone_number ?? $request->user()->phone_number,
            'initiator_id' => config('services.eko.initiator_id'),
            'client_ref_id' => $reference_id,
            'state' => 1,
            'channel' => $request->mode,
            'user_code' => $request->user()->eko_user_code
        ];

        $response = Http::withHeaders($this->ekoHeaders())->asForm()
            ->post(config('services.eko.base_url') . '/ekoapi/v2/transactions', $data);

        return $this->processResponse($response, 'eko');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
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
}
