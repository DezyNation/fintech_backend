<?php

namespace App\Http\Controllers\Services\Payout;

use App\Http\Controllers\Controller;
use App\Http\Requests\PayoutRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BranchxController extends Controller
{
    public function initiateTransaction(
        PayoutRequest $request,
        string $reference_id,
    ) {
        $data = [
            "amount" => $request->amount,
            "mobileNumber" => $request->user()->phone_number,
            "requestId" => $reference_id,
            "accountNumber" => $request->account_number,
            "ifscCode" => $request->ifsc_code,
            "beneficiaryName" => $request->beneficiary_name,
            "remitterName" => $request->user()->name,
            "bankName" => $request->bank_name ?? "HDFC Bank",
            "transferMode" => strtoupper($request->mode),
            "latitude" => "11.0297079",
            "longitude" => "77.0338217",
            "emailId" => $request->user()->email,
            "purpose" => "Contractor Payment",
        ];

        $response = Http::asJson()
            ->withHeaders([
                "apiToken" => config("services.branchx.api_token"),
            ])
            ->post(
                config("services.branchx.base_url") . "/service/payout/v2",
                $data,
            );

        Log::info("branchx_resp", [$response->body()]);
        if ($response->failed()) {
            Log::info("branchx_request", $data);
            $this->releaseLock($request->user()->id);
            abort(
                $response->status(),
                $response["message"] ?? "Gateway Failure!",
            );
        }

        return $this->processResponse($response);
    }

    public function updateTransaction(string $referenceId)
    {
        $response = Http::asJson()
            ->withHeaders([
                "apiToken" => config("services.branchx.api_token"),
            ])
            ->post(
                config("services.branchx.base_url") .
                    "/service/status_check/v2",
                ["requestId" => $referenceId],
            );

        if ($response->failed()) {
            abort(
                $response->status(),
                $response["message"] ?? "Gateway Failure!",
            );
        }

        Log::info("branchx_upd_resp", [$response->body()]);
        return $this->processUpdateResponse($response);
    }

    public function processUpdateResponse($response)
    {
        switch (strtoupper($response["data"]["status"])) {
            case "SUCCESS":
                // Handle successful response
                $data = [
                    "status" => "success",
                    "message" => $response["data"]["message"],
                    "utr" => $response["data"]["opRefId"] ?? null,
                    "transaction_status" => strtolower($response["status"]),
                ];
                break;
            case "FAILURE":
                // Handle failed response
                $data = [
                    "status" => "failed",
                    "message" => $response["data"]["message"],
                    "utr" => $response["data"]["opRefId"] ?? null,
                    "transaction_status" => strtolower(
                        $response["status"] ?? "failed",
                    ),
                ];
                break;
            case "PENDING":
                // Handle pending response
                $data = [
                    "status" => "success",
                    "message" => $response["data"]["message"],
                    "utr" => $response["data"]["opRefId"] ?? null,
                    "transaction_status" => strtolower($response["status"]),
                ];
                break;
            default:
                $data = [
                    "status" => "failed",
                    "message" => $response["data"]["message"],
                    "utr" => $response["data"]["opRefId"] ?? null,
                    "transaction_status" => strtolower(
                        $response["status"] ?? "failed",
                    ),
                ];
                break;
        }
        return ["data" => $data, "response" => $response->body()];
    }

    public function processResponse($response)
    {
        switch (strtoupper($response->json("status"))) {
            case "SUCCESS":
                // Handle successful response
                $data = [
                    "status" => "success",
                    "message" => $response["message"],
                    "utr" => $response["data"]["utr"] ?? null,
                    "transaction_status" => strtolower($response["status"]),
                ];
                break;
            case "FAILURE":
                // Handle failed response
                $data = [
                    "status" => "failed",
                    "message" => $response["message"],
                    "utr" => $response["data"]["utr"] ?? null,
                    "transaction_status" => strtolower(
                        $response["status"] ?? "failed",
                    ),
                ];
                break;
            case "PENDING":
                // Handle pending response
                $data = [
                    "status" => "success",
                    "message" => $response["message"],
                    "utr" => $response["data"]["utr"] ?? null,
                    "transaction_status" => strtolower($response["status"]),
                ];
                break;
            default:
                $data = [
                    "status" => "failed",
                    "message" => $response["message"],
                    "utr" => $response["data"]["utr"] ?? null,
                    "transaction_status" => strtolower(
                        $response["status"] ?? "failed",
                    ),
                ];
                break;
        }
        return ["data" => $data, "response" => $response->body()];
    }
}
