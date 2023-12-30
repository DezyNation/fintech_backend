<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    public function ekoHeaders(): array
    {
        $encoded_key = base64_encode(env('KEY'));
        $secret_key_timestamp = (int)round(microtime(true), 1000);
        $signature = hash_hmac("SHA256", $secret_key_timestamp, $encoded_key, true);
        $secret_key = base64_encode($signature);

        return [
            'developer_key' => env('DEVELOPER_KEY'),
            'secret-key' => $secret_key,
            'secret-key-timestamp' => $secret_key_timestamp
        ];
    }

    /**
     * secret key
     * secret key timestamp
     * public key
     * data
     */
    public function requestHash(array $data)
    {
        $headers = $this->ekoHeaders();
        $string = $headers['secre-key-timestamp'] . $data['utility_number'] . $data['amount'] . $data['user_code'];
        $signature_request_hash = hash_hmac("SHA256", $string, base64_encode(env('KEY')), true);
        $request_hash = base64_encode($signature_request_hash);
        return ['request_hash' => $request_hash];
    }

    public function triggerSms(array $contents)
    {
        //Http request for message triggers
    }
}
