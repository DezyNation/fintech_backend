<?php

namespace App\Http\Controllers\Services\AePS;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\MerchantAuthRequest;
use App\Http\Controllers\Services\AePS\EkoController;
use App\Http\Controllers\Services\AePS\PaysprintController;
use App\Http\Controllers\TransactionController;
use App\Http\Requests\AepsTrxnRequest;

class FlowController extends Controller
{
    /**
     * -----------------------------------------
     * All AePS requests will be processed here
     * -----------------------------------------
     * Step 1: Middleware checks
     * Step 2: Optimistic/Pessimistic Locking for
     *         all monetary requests
     * Step 3: Validate all requests
     * Step 4: API calls
     * Step 5: Commit or rollback transactions (depends on response)
     */
    public function authentication(MerchantAuthRequest $request): JsonResponse
    {
        // Eko request
        $eko = new EkoController();
        $response = $eko->merchantAuthentication($request);
        return response()->json(['reference_tid' => $response['data']['reference_tid']], 200);

        // Paysprint Request
        $paysprint = new PaysprintController();
        $response = $paysprint->merchantAuthentication($request);
        return response()->json(['reference_tid' => $response['MerAuthTxnId']], 200);
    }

    /**
     * Validate request first
     * Identify service using given array
     * Make API call and store response
     * Commission distribution
     * Return data
     */
    public function transactions(AepsTrxnRequest $request): JsonResponse
    {
        $user = $request->user();
        $services = ['MS' => 1, 'CW' => 2, 'BE' => 3];

        //Eko Request
        $eko = new EkoController();
        $response = $eko->aepsTransaction($request, $services[$request->serviceType]);
        $result = $this->processResponse($response, $request);

        // Paysprint Request
        $paysprint = new PaysprintController();
        $response = $paysprint->aepsTransaction($request);
        $result = $this->processResponse($response, $request);

        TransactionController::store($user->id, $response['reference_id'], "AEPS-{$request->serviceType}", "Random desc", 100, 100, $result);
        $commission = new CommissionController();
        $commission->distributeCommission($user, $request->serviceType, $request->amount);

        return response()->json($result, 200);
    }

    public function processResponse($response, $request): array
    {
        //Eko Response
        $result = [
            'status' => $response['status'],
            'reference_id' => $request['client_ref_id'],
            'amount' => $response['data']['amount'],
            'message' => $response['message'],
            'aadhar' => substr($request['aadhar'], 0, -8),
            'transaction_time' => $response['data']['transaction_time']
        ];

        //Paysprint Response
        $result = [
            'status' => $response['status'],
            'reference_id' => $request['client_ref_id'],
            'amount' => $response['data']['amount'],
            'message' => $response['message'],
            'aadhar' => substr($request['aadhar'], 0, -8),
            'transaction_time' => $request['transaction_time']
        ];

        return $result;
    }
}
