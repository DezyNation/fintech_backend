<?php

namespace App\Http\Controllers\Services\Payout;

use App\Http\Controllers\Controller;
use App\Http\Requests\PayoutRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PayliveController extends Controller
{
    public function initiateTransaction(
        PayoutRequest $request,
        string $reference_id,
    ) {
        $name = preg_split("/\s+/", trim($request->beneficiary_name));
        $data = [
            "externalTransactionId" => $reference_id,
            "Email" => $request->user()->email,
            "mode" => strtolower($request->mode),
            "Mobile" => $request->user()->phone_number,
            "Ifsc" => $request->ifsc_code,
            "Bank" => $request->bank_name ?? "HDFC Bank",
            "accountNumber" => $request->account_number,
            "Amount" => $request->amount,
            "FirstName" => $name[0] ?? "John",
            "LastName" =>
                count($name) > 1 ? implode(" ", array_slice($name, 1)) : "Doe",
        ];

        $response = Http::asJson()
            ->withHeaders([
                "secretkey" => config("services.paylive.secret_key"),
                "saltkey" => config("services.paylive.salt_key"),
            ])
            ->post(
                config("services.paylive.base_url") .
                    "/api/v2/settelment/transaction",
                $data,
            );

        if ($response->failed()) {
            $this->releaseLock($request->user()->id);
            Log::info("failed_paylive", [$response->body()]);
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
                "secretkey" => config("services.paylive.secret_key"),
                "saltkey" => config("services.paylive.salt_key"),
            ])
            ->post(
                config("services.paylive.base_url") .
                    "/api/v2/settelment/check-status",
                ["externalTransactionId" => $referenceId],
            );

        if ($response->failed()) {
            abort(
                $response->status(),
                $response["message"] ?? "Gateway Failure!",
            );
        }

        return $this->processUpdateResponse($response);
    }

    public function processUpdateResponse($response)
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

    public function processResponse($response)
    {
        switch (strtoupper($response->json("status"))) {
            case "SUCCESS":
                // Handle successful response
                $data = [
                    "status" => "success",
                    "message" => $response["message"],
                    "utr" => $response["utr"] ?? null,
                    "transaction_status" => strtolower($response["status"]),
                ];
                break;
            case "FAILURE":
                // Handle failed response
                $data = [
                    "status" => "failed",
                    "message" => $response["message"],
                    "utr" => $response["utr"] ?? null,
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
                    "utr" => $response["utr"] ?? null,
                    "transaction_status" => strtolower($response["status"]),
                ];
                break;
            default:
                $data = [
                    "status" => "failed",
                    "message" => $response["message"],
                    "utr" => $response["utr"] ?? null,
                    "transaction_status" => strtolower(
                        $response["status"] ?? "failed",
                    ),
                ];
                break;
        }
        return ["data" => $data, "response" => $response->body()];
    }
}
