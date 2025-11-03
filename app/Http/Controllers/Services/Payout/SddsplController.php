<?php

namespace App\Http\Controllers\Services\Payout;

use App\Http\Controllers\Controller;
use App\Http\Requests\PayoutRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SddsplController extends Controller
{
    public function login()
    {
        $key = "login_token_sddspl";
        if (Cache::has($key)) {
            return Cache::get($key);
        } else {
            $data = [
                "username" => config("services.sddspl.username"),
                "password" => config("services.sddspl.password"),
                "otp" => "yes",
                "browser_id" => "52e49c456f92b900cf0ed2e20172a7c2",
                "lat" => "26.9194401",
                "long" => "75.7531271",
            ];

            $response = Http::withoutVerifying()->post(
                config("services.sddspl.base_url") . "/api/api-login",
                $data,
            );

            if ($response["status"] == true) {
                Cache::put(
                    $key,
                    $response["data"]["authorisation"]["token"],
                    now()->addHours(23),
                );
                return $response["data"]["authorisation"]["token"];
            } else {
                Log::info($response->body(), ["login_fail_sddspl"]);
                abort(400, "Login failed");
            }
        }
    }

    public function initiateTransaction(PayoutRequest $request)
    {
        $token = $this->login();
        $data = [
            "TRANSFER_TYPE_DESC" => strtoupper($request->mode),
            "BENE_BANK" => $request->bank_name ?? "HDFC Bank",
            "INPUT_DEBIT_AMOUNT" => $request->amount,
            "INPUT_VALUE_DATE" => date("d/m/Y"),
            "TRANSACTION_TYPE" => "SINGLE",
            "BENE_ACC_NAME" => $request->beneficiary_name,
            "BENE_ACC_NO" => $request->account_number,
            "BENE_BRANCH" => "NA",
            "BENE_IDN_CODE" => $request->ifsc_code,
            "REMITTER_NUMBER" => config("services.sddspl.remitter_id"),
        ];

        $beneficiary = $this->createBeneficiary($data);
        $data["REMITTER_BENE_ID"] = $beneficiary;

        $response = Http::withToken($token)
            ->withoutVerifying()
            ->acceptJson()
            ->asJson()
            ->post(
                config("services.sddspl.base_url") .
                    "/api/hdfc/cbx-transaction-api",
                $data,
            );
        if ($response->failed()) {
            $data = [
                "status" => "failed",
                "message" => $response["message"] ?? "Failure",
                "utr" => null,
                "transaction_status" => "failed",
            ];
            return ["data" => $data, "response" => $response->body()];
        }
        return $this->processResponse($response);
    }

    public function createBeneficiary($input)
    {
        $token = $this->login();
        $this->remitterLogin();
        $phone = auth()->user()->phone_number ?? "9971914198";
        $data = [
            "mobile" => config("services.sddspl.remitter_id"),
            "bank_name" => $input["BENE_BANK"],
            "bank_account_number" => $input["BENE_ACC_NO"],
            "bank_account_holder_name" => $input["BENE_ACC_NAME"],
            "bank_ifsc" => $input["BENE_IDN_CODE"],
            "BeneficiaryMobile" => $phone,
            "status" => 1,
        ];

        $response = Http::withHeader("Authorization", "Bearer " . $token)
            ->asJson()
            ->acceptJson()
            ->withoutVerifying()
            ->post(
                config("services.sddspl.base_url") .
                    "/api/remitter-bank-details/add_bank",
                $data,
            );

        if ($response->failed()) {
            $data = [
                "status" => "failed",
                "message" => $response["message"] ?? "Failure",
                "utr" => null,
                "transaction_status" => "failed",
            ];
            return ["data" => $data, "response" => $response->body()];
        }

        if ($response["status"] == true) {
            return $response["data"]["id"];
        } else {
            Log::info($response, ["bene_fail"]);
            abort(400, $response["message"] ?? "Failure");
        }
    }

    public function remitterLogin()
    {
        $token = $this->login();
        $data = [
            "mobileNumber" => config("services.sddspl.remitter_id"),
            "lat" => "26.9194401",
            "long" => "75.7728197",
        ];

        Http::withoutVerifying()
            ->asJson()
            ->acceptJson()
            ->withToken($token)
            ->post(
                config("services.sddspl.base_url") .
                    "/api/financial-services/mobile-verify",
                $data,
            );
    }

    public function processResponse($response)
    {
        if ($response["status"] == true) {
            $data = [
                "status" => "success",
                "message" => $response["message"] ?? $response["status"],
                "utr" => $response["data"]["utr_no"] ?? $response["utr_no"],
                "transaction_status" => strtolower(
                    $response["data"]["status"] ?? $response["status"],
                ),
                "reference_id" => $response["data"]["paymentrefno"],
            ];
        } else {
            $data = [
                "status" => "failed",
                "message" => $response["message"] ?? "Failed",
                "utr" => null,
                "transaction_status" => "failed",
            ];
        }

        return ["data" => $data, "response" => $response->body()];
    }

    public function updateResponse($response)
    {
        $data = [
            "status" => "success",
            "message" => $response["status"] ?? "No message relayed",
            "utr" => $response["utr_no"],
            "transaction_status" => strtolower($response["status"]),
        ];

        return ["data" => $data, "response" => $response->body()];
    }

    public function updateTransaction(string $reference_id)
    {
        $response = Http::withToken($this->login())->get(
            config("services.sddspl.base_url") .
                "/api/hdfc/fund-transfer-front-status/$reference_id",
        );

        if ($response->failed()) {
            abort(400, $response["message"]);
        } else {
            return $this->updateResponse($response);
        }
    }
}
