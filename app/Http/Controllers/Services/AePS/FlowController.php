<?php

namespace App\Http\Controllers\Services\AePS;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\MerchantAuthRequest;
use App\Http\Controllers\Services\AePS\EkoController;
use App\Http\Controllers\Services\AePS\PaysprintController;

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

    public function transactions(Request $request): JsonResponse
    {
        // Eko request
        $eko = new EkoController();
        $response = $eko->aepsTransaction($request);
        return response()->json(['reference_tid' => $response['data']['reference_tid']], 200);

        // Paysprint Request
        $paysprint = new PaysprintController();
        $response = $paysprint->merchantAuthentication($request);
        return response()->json(['reference_tid' => $response['MerAuthTxnId']], 200);
    }
}
