<?php

namespace App\Http\Controllers\Services\Payout;

use App\Http\Controllers\Controller;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class PaysprintController extends Controller
{
    public function encrypt(array $data): array
    {
        $key = openssl_random_pseudo_bytes(32);
        $encrypted_data = openssl_encrypt(json_encode($data), 'AES-256-ECB', $key, OPENSSL_RAW_DATA);
        openssl_public_encrypt($key, $encrypted_key, file_get_contents(storage_path('keys/paysprint/public.key')));
        $encoded_data = base64_encode($encrypted_data);
        $encoded_key = base64_encode($encrypted_key);

        return [
            'body' => [
                'payload' => $encoded_data,
                'key' => $encoded_key,
                'partnerId' => config('services.paysprint.partner_id'),
                'clientid' => base64_encode("SPR_NXT_uat_977a8fbbbcef6587")
            ],

            'headers' => [
                'key' => $encoded_key,
                'partnerId' => config('services.paysprint.partner_id'),
                'client-id' => base64_encode("SPR_NXT_uat_977a8fbbbcef6587")
            ]
        ];
    }

    public function decrypt(string $key, string $body)
    {
        $decoded_data = base64_decode($body);
        $decoded_key = base64_decode($key);
        openssl_private_decrypt($decoded_key, $decrypted_key, file_get_contents(storage_path('keys/paysprint/private.key')));
        $decrypted_data = openssl_decrypt($decoded_data, 'AES-256-ECB', $decrypted_key, OPENSSL_RAW_DATA);
        return json_decode($decrypted_data);
    }

    public function processResponse(Response $response)
    {
        $decrypt = json_decode($this->decrypt($response->header('key'), $response['body']));

        switch ($decrypt->status_code) {
            case 200:
                    $data = [
                    'status' => 'success',
                    'message' => $decrypt->message,
                    'utr' => $decrypt->data->utr ?? null,
                    'transaction_status' => strtolower($decrypt->status)
                ];
                break;

            default:
                $data = [
                    'status' => 'error',
                    'message' => $decrypt->message ?? "An error occurred while processing your request",
                ];
                break;
        }

        return ['data' => $data, 'response' => $decrypt];
    }

    public function initiateTransaction()
    {
        $data =  [
            "apiId" => "30008",
            "bankId" => "5",
            "acctNumber" => "409002136531",
            "beneAcctNumber" => "4166451441216238",
            "amount" => "100",
            "purpose" => "payout",
            "type" => 1,
            "name" => "Paysprint",
            "mobile" => "9934464262",
            "ifsc" => "KKBK0000958",
            "bankname" => "Kotak",
            "branchname" => "Delhi",
            "beneaddress" => "Delhi",
            'mode' => 'imps',
            "transferId" => uniqid('PYT-')
        ];

        $encrypted_data = $this->encrypt($data);
        $response = Http::withHeaders($encrypted_data['headers'])
            ->post(config('services.paysprint.base_url') . '/api/v1/payout/PAYOUT', ['body' => $encrypted_data['body']]);
        ($response['code'] == 200) ? $decrypted_data =  $this->processResponse($response, $response['code']) : abort(400, $response['message']);

        return $decrypted_data;
    }

    public function update()
    {
        $data =  [
            "apiId" => "30013",
            "bankId" => "5",
            "acctNumber" => "409002136531",
            "transferId" => uniqid('PYT-')
        ];

        $encrypted_data = $this->encrypt($data);
        $response = Http::withHeaders($encrypted_data['headers'])
            ->post(config('services.paysprint.base_url') . '/api/v1/payout/PAYOUT', ['body' => $encrypted_data['body']]);
        ($response['code'] == 200) ? $decrypted_data =  $this->processResponse($response, $response['code']) : abort(400, $response['message']);
        return $decrypted_data;
    }
}