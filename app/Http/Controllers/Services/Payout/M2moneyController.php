<?php

namespace App\Http\Controllers\Services\Payout;

use App\Http\Controllers\Controller;
use App\Http\Requests\PayoutRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class M2moneyController extends Controller
{
    public function createContact(PayoutRequest $request): string
    {
        $nameParts = explode(" ", trim($request->beneficiary_name), 2);
        $firstName = $nameParts[0] ?? $request->beneficiary_name;
        $lastName = $nameParts[1] ?? "";

        $data = [
            "firstName" => $firstName,
            "lastName" => $lastName,
            "email" => $request->user()->email,
            "mobile" => $request->user()->phone_number,
            "type" => "customer",
            "bankName" => $request->bank_name ?? "",
            "accountType" => "bank_account",
            "accountNumber" => $request->account_number,
            "ifsc" => $request->ifsc_code,
            "referenceId" => "REF" . time(),
        ];

        $response = Http::withBasicAuth(
            config("services.m2m.username"),
            config("services.m2m.password"),
        )
            ->asJson()
            ->post(
                config("services.m2m.base_url") . "/v1/service/payout/contacts",
                $data,
            );

        Log::info("m2m_create_contact_resp", [$response->body()]);

        if (
            $response->failed() ||
            strtoupper($response["status"]) === "FAILURE"
        ) {
            $this->releaseLock($request->user()->id);
            abort(
                $response->status(),
                $response["message"] ?? "Failed to create contact.",
            );
        }

        return $response["data"]["contactId"];
    }

    public function initiateTransaction(
        PayoutRequest $request,
        string $reference_id,
    ): array {
        $contactId = $this->createContact($request);

        $data = [
            "amount" => (string) $request->amount,
            "purpose" => "others",
            "mode" => strtoupper($request->mode),
            "contactId" => $contactId,
            "clientRefId" => $reference_id,
            "udf1" => "",
            "udf2" => "",
            "latitude" => "77.25382",
            "longitude" => "28.40082",
        ];

        $response = Http::withBasicAuth(
            config("services.m2m.username"),
            config("services.m2m.password"),
        )
            ->asJson()
            ->post(
                config("services.m2m.base_url") . "/v1/service/payout/orders",
                $data,
            );

        Log::info("m2m_initiate_resp", [$response->body()]);
        if (
            $response->failed() ||
            strtoupper($response["status"]) === "FAILURE" ||
            strtoupper($response["status"]) == "MISSING_PARAMETER"
        ) {
            $this->releaseLock($request->user()->id);
            abort(
                400,
                $response["message"] ?? "Failed to initiate transaction.",
            );
        }

        return $this->processResponse($response);
    }

    public function updateTransaction(string $reference_id): array
    {
        $response = Http::withBasicAuth(
            config("services.m2m.username"),
            config("services.m2m.password"),
        )
            ->asJson()
            ->get(
                config("services.m2m.base_url") .
                    "/v1/service/payout/orders/" .
                    $reference_id,
            );

        Log::info("m2m_status_resp", [$response->body()]);

        if ($response->failed()) {
            abort(
                400,
                $response["message"] ?? "Failed to fetch transaction status.",
            );
        }

        return $this->processResponse($response);
    }

    public function processResponse(Response $response): array
    {
        $status = strtoupper($response["status"] ?? "");
        $data = $response["data"] ?? [];

        switch ($status) {
            case "SUCCESS":
                $result = [
                    "status" => "success",
                    "message" => $response["message"],
                    "utr" => $data["orderRefId"] ?? null,
                    "transaction_status" => $data['status'],
                ];
                break;

            case "PENDING":
                $result = [
                    "status" => "success",
                    "message" => $response["message"],
                    "utr" => $data["orderRefId"] ?? null,
                    "transaction_status" => $data['status'],
                ];
                break;

            case "FAILURE":
                $result = [
                    "status" => "error",
                    "message" =>
                        $response["message"] ??
                        ($data["failedMessage"] ?? "Transaction failed."),
                    "utr" => $data["orderRefId"] ?? null,
                    "transaction_status" => $data['status'],
                ];
                break;

            default:
                // Handles 'queued' or any other intermediate state from order creation
                $result = [
                    "status" => "success",
                    "message" =>
                        $response["message"] ??
                        "Transaction has been initiated.",
                    "utr" => $data["orderRefId"] ?? null,
                    "transaction_status" => $data['status'],
                ];
                break;
        }
        if($result['transaction_status'] == 'queued' || $result['transaction_status'] == 'processing')
        {
            $result['transaction_status'] = 'pending';
        }

        return ["data" => $result, "response" => $response->body()];
    }
}
