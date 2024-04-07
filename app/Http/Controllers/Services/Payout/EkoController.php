<?php

namespace App\Http\Controllers\Services\Payout;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\PayoutRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
        $this->activateService($request, $service_code = 45);
        $data = [
            'initiator_id' => config('services.eko.initiator_id'),
            'client_ref_id' => $reference_id,
            'service_code' => $service_code,
            'payment_mode' => $request->mode,
            'recipient_name' => $request->beneficiary_name,
            'account' => $request->accont_number,
            'ifsc' => $request->ifsc_code,
            'sender_name' => $request->user()->name,
        ];

        $response = Http::withHeaders($this->ekoHeaders())->asForm()
            ->post(config('services.eko.base_url') . "/ekoapi/v1/user_code:{$request->user()->eko_user_code}/settlement", $data);

        Log::info($response);

        return $this->processResponse($response, 'eko');
    }

    public function activateService(PayoutRequest $request, int $service_code)
    {
        $data = [
            'initiator_id' => config('services.eko.initiator_id'),
            'user_code' => $request->user()->eko_user_code,
            'service_code' => $service_code
        ];
        Log::info($data);

        $response = Http::withHeaders($this->ekoHeaders())->asMultipart()
            ->put(config('services.eko.base_url') . '/ekoapi/v1/user/service/activate', $data);

        Log::info($response->status());

        if ($response->failed()) {
            $this->releaseLock($request->user()->id);
            abort(403, $response['message'] ?? "Failed.");
        }

        if ($response['status'] == 0 && $response['data']['service_status'] == 1) {
            return true;
        } else {
            $this->releaseLock($request->user()->id);
            abort(403, $response['message'] ?? "Failed to Activate Service");
        }
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
