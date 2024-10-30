<?php

namespace App\Http\Controllers\Services\Payout;

use App\Http\Controllers\Controller;
use App\Http\Requests\PayoutRequest;
use Illuminate\Support\Facades\Http;

class SafexpayController extends Controller
{

    public function processResponse($response): array
    {

        if (in_array($response['response']['code'], ["0001", "0000"])) {
            $utr = ($response['payOutBean']['bankRefNo'] == 'NA') ? null : $response['payOutBean']['bankRefNo'];
            $data = [
                'status' => 'success',
                'description' => $response['response']['description'],
                'utr' => $utr,
                'transaction_status' => strtolower($response['payOutBean']['txnStatus'])
            ];
        } else {
            $utr = ($response['payOutBean']['bankRefNo'] == 'NA') ? null : $response['payOutBean']['bankRefNo'];
            $data = [
                'status' => 'failed',
                'message' => $response['response']['description'],
                'transaction_status' => strtolower($response['payOutBean']['txnStatus']),
                'utr' => $utr
            ];
        }

        return ['data' =>  $data];
    }


    public function encrypt(string $data, string $key, $type = '', $iv = "0123456789abcdef", $size = 16)
    {
        $pad = $size - (strlen($data) % $size);
        $padtext = $data . str_repeat(chr($pad), $pad);
        $crypt = openssl_encrypt(
            $padtext,
            "AES-256-CBC",
            base64_decode($key),
            OPENSSL_RAW_DATA
                | OPENSSL_ZERO_PADDING,
            $iv
        );
        return base64_encode($crypt);
    }

    public function decrypt(string $encrypt, string $key, string $iv)
    {
        $crypt = base64_decode($encrypt);
        $padtext = openssl_decrypt(
            $crypt,
            "AES-256-CBC",
            base64_decode($key),
            OPENSSL_RAW_DATA
                | OPENSSL_ZERO_PADDING,
            $iv
        );
        $pad = ord($padtext[strlen($padtext) - 1]);
        if ($pad > strlen($padtext)) {
            return false;
        }
        if (strspn($padtext, $padtext[strlen($padtext) - 1], strlen($padtext) - $pad) != $pad) {
            $text = "Error";
            return $text;
        }
        return json_decode($padtext, true);
    }

    public function initiateTransaction()
    {
        $refernce_id = uniqid('sfx-tst');
        $data = json_encode([
            'header' => [
                'operatingSystem' => 'WEB',
                'sessionId' => config('services.safexpay.merchant_id'),
                'version' => '1.0.0'
            ],
            'userInfo' => "{}",
            'transaction' => [
                'requestType' => 'WTW',
                'requestSubType' => 'PWTB',
                'tranCode' => 0,
                'txnAmt' => 0.0,
                'id' => config('services.safexpay.merchant_id'),
                'surChargeAmt' => 0,
                'txnCode' => 0,
                'userType' => 0
            ],
            'payOutBean' => [
                'mobileNo' => 9999999999,
                'txnAmount' => 10,
                'accountNo' => 41362834643,
                'ifscCode' => "SBIN0032284",
                'bankName' => "SBI",
                "txnType" => "IMPS",
                'accountHolderName' => "TEST",
                'emailId' => "john@email.com",
                'orderRefNo' => $refernce_id,
                'count' => 0
            ]
        ]);
        $encryption = $this->encrypt($data, config('services.safexpay.merchant_key'));

        $payload = [
            'uId' => config('services.safexpay.merchant_id'),
            'payload' => $encryption
        ];

        $response = Http::post(config('services.safexpay.base_url'), $payload);

        $decrypt = $this->decrypt($response['payload'], config('services.safexpay.merchant_key'), config('services.safexpay.iv'));

        // if ($response->failed()) {
        //     $this->releaseLock($request->user()->id);
        //     abort($response->status(), "Gateway Failure!");
        // }

        return $this->processResponse($decrypt);
    }
}
