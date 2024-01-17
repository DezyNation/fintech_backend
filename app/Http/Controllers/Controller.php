<?php

namespace App\Http\Controllers;

use Firebase\JWT\JWT;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    /**
     * Eko headers
     */
    public function ekoHeaders(): array
    {
        $encoded_key = base64_encode(env('EKO_KEY'));
        $secret_key_timestamp = (int)round(microtime(true) * 1000);
        $signature = hash_hmac('SHA256', $secret_key_timestamp, $encoded_key, true);
        $secret_key = base64_encode($signature);

        return [
            'developer_key' => env('DEVELOPER_KEY'),
            'secret-key' => $secret_key,
            'secret-key-timestamp' => $secret_key_timestamp
        ];
    }

    /**
     * return array
     * secret key
     * secret key timestamp
     * data
     */
    public function requestHash(array $data): array
    {
        $headers = $this->ekoHeaders();
        $string = $headers['secret-key-timestamp'] . $data['utility_number'] . $data['amount'] . $data['user_code'];
        $signature_request_hash = hash_hmac("SHA256", $string, base64_encode(env('EKO_KEY')), true);
        $request_hash = base64_encode($signature_request_hash);
        return array_merge($headers, ['request_hash' => $request_hash]);
    }

    /**
     * Token Generation for Paysprint
     */
    public function paysprintHeaders(): array
    {
        $key = env('PAYSPRINT_JWT');
        $payload = [
            'timestamp' => time(),
            'partnerId' => env('PAYSPRINT_PARTNERID'),
            'reqid' => abs(crc32(uniqid()))
        ];

        $jwt = JWT::encode($payload, $key, 'HS256');
        return [
            'Token' => $jwt,
            'Authorisedkey' => env('AUTHORISED_KEY')
        ];
    }

    public function triggerSms(array $contents)
    {
        //Http request for message triggers
    }
}
