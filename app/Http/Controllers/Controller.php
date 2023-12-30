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

    public function triggerSms(array $contents)
    {
        //Http request for message triggers
    }
}
