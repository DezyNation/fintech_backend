<?php

namespace App\Http\Controllers\Services\Payout;

use App\Http\Controllers\Controller;
use App\Http\Requests\PayoutRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use function GuzzleHttp\json_decode;

class ZapayController extends Controller
{
    public function authorizeRequest(): string
    {
        return Cache::remember(
            "zapay24_access_token",
            now()->addMinutes(55),
            function () {
                $baseUrl = config("services.zapay24.base_url");
                $response = Http::post(
                    "{$baseUrl}/payout/Auth/1.0/getAuthToken",
                    [
                        "username" => config("services.zapay24.username"),
                        "password" => config("services.zapay24.password"),
                    ],
                );

                Log::info("zapay24_auth_resp", [
                    "status" => $response->status(),
                    "body" => $response->body(),
                ]);

                if ($response->failed()) {
                    abort(
                        $response->status(),
                        $response->json("message") ??
                            "Failed to authenticate with Zapay24.",
                    );
                }

                $token = $response->json("token");

                if (!$token) {
                    abort(
                        500,
                        "Zapay24 authentication succeeded but token missing.",
                    );
                }

                return $token;
            },
        );
    }

    public function headers(): array
    {
        return [
            "Authorization" => "Bearer " . $this->authorizeRequest(),
            "Accept" => "application/json",
        ];
    }

    public function initiateTransaction(
        PayoutRequest $request,
        string $reference_id,
    ): array {
        $merchant_id = config("services.zapay24.merchant_id");
        $reference_id = preg_replace(
            "/^PAY/",
            "$merchant_id" . "_",
            $reference_id,
        );
        $data = [
            "transaction_type" => "ReqPay",
            "transaction_mode" => strtoupper($request->mode),
            "transaction_id" => $reference_id,
            "device_location" => $request->ip() ?? "",
            "payeeInfo" => [
                "credit_account_no" => $request->account_number,
                "credit_account_bank" =>
                    $request->bank_name ?? "State Bank of India.",
                "credit_account_ifsc" => $request->ifsc_code,
                "credit_account_name" => $request->beneficiary_name,
                "credit_amount" => $request->amount,
                "credit_remark" => "Contractor Payment",
            ],
            "custInfo" => [
                "customer_mobile" => $request->user()->phone_number,
                "customer_email" => $request->user()->email ?? "",
            ],
        ];

        $response = Http::withHeaders($this->headers())->post(
            config("services.zapay24.base_url") . "/payout/Payout/1.0/ReqPay",
            $data,
        );

        $decoded = json_decode(json_decode($response->body(), true), true);
        Log::info("zapay24_initiate_decoded", [$decoded]);
        if ($response->failed()) {
            $this->releaseLock($request->user()->id);
            abort(400, $decoded["message"]);
        }

        return $this->processResponse($decoded, $reference_id);
    }

    public function updateTransaction(string $reference_id): array
    {
        $data = [
            "transaction_type" => "ReqStatus",
            "transaction_mode" => "IMPS",
            "transaction_id" => $reference_id,
            "org_transaction_id" => $reference_id,
        ];

        $response = Http::withHeaders($this->headers())->post(
            config("services.zapay24.base_url") .
                "/payout/Payout/1.0/ReqStatus",
            $data,
        );

        Log::info("zapay24_status_resp", [$response->body()]);

        if ($response->failed()) {
            abort(
                $response->status(),
                $response["message"] ?? "Gateway Failure!",
            );
        }

        return $this->processResponse($response, $reference_id);
    }

    public function processResponse($response, $reference_id): array
    {
        $txnInfo = $response["transactioninfo"] ?? [];
        $status = strtolower($txnInfo["status"] ?? "");

        switch ($status) {
            case "success":
                $data = [
                    "status" => "success",
                    "message" => $txnInfo["message"] ?? $response["message"],
                    "utr" => $txnInfo["utr_no"] ?? null,
                    "transaction_status" => "success",
                    "reference_id" => $reference_id,
                ];
                break;

            case "pending":
            case "processing":
                $data = [
                    "status" => "success",
                    "message" => $txnInfo["message"] ?? $response["message"],
                    "utr" => $txnInfo["utr_no"] ?? null,
                    "transaction_status" => "pending",
                    "reference_id" => $reference_id,
                ];
                break;

            case "failed":
            case "error":
            case "reversed":
                $data = [
                    "status" => "error",
                    "message" =>
                        $txnInfo["message"] ??
                        ($response["message"] ?? "Transaction failed."),
                    "utr" => $txnInfo["utr_no"] ?? null,
                    "transaction_status" => "failed",
                    "reference_id" => $reference_id,
                ];
                break;

            default:
                // Handles initial payout response where transactioninfo may not yet be present
                $data = [
                    "status" => "error",
                    "message" =>
                        $response["message"] ??
                        "Transaction has been initiated.",
                    "utr" => null,
                    "transaction_status" => "pending",
                    "reference_id" => $reference_id,
                ];
                break;
        }

        return ["data" => $data, "response" => $response];
    }
}
