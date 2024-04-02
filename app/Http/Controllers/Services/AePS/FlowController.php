<?php

namespace App\Http\Controllers\Services\AePS;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\MerchantAuthRequest;
use App\Http\Requests\AepsTransactionRequest;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\Services\AePS\EkoController;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Http\Controllers\Services\AePS\PaysprintController;
use App\Http\Resources\GeneralResource;
use Illuminate\Http\Resources\Json\JsonResource;

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
    public function transactions(AepsTransactionRequest $request): JsonResource
    {
        $lock = $this->lockRecords($request->user()->id);
        if (!$lock->get()) {
            throw new HttpResponseException(response()->json(['data' => ['message' => "Failed to acquire lock"]], 423));
        }

        $class_name = Str::of($request->provider . "_" . "controller")->studly();
        $class = __NAMESPACE__ . "\\" . $class_name;
        $instance = new $class;
        if (!class_exists($class)) {
            abort(501, ['data' => ['message' => "Provider not supported."]]);
            $lock->release();
        }

        $reference_id = uniqid('PYT');

        $transaction = $instance->initiateTransaction($request, $reference_id);

        if ($transaction['metadata']['status'] != 'success') {
            $lock->release();
            abort(400, $transaction['metadata']['message']);
        }

        TransactionController::store($request->user(), $reference_id, 'payout', "Payout initiated", 0, $request->amount, []);
        $commission_class = new CommissionController;
        $commission_class->distributeCommission($request->user(), $request->service, $request->amount);

        $lock->release();

        return new GeneralResource("");
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
