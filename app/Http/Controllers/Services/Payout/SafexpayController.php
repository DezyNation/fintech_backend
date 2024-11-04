<?php

namespace App\Http\Controllers\Services\Payout;

use App\Http\Controllers\Controller;
use App\Http\Requests\PayoutRequest;
use Illuminate\Support\Facades\Http;

class SafexpayController extends Controller
{

    public function processResponse($response): array
    {

        if (in_array($response->response->code, ["0001", "0000"])) {
            $utr = ($response->payOutBean->bankRefNo == 'NA') ? null : $response->payOutBean->bankRefNo;
            $data = [
                'status' => 'success',
                'description' => $response->response->description,
                'utr' => $utr,
                'transaction_status' => strtolower($response->payOutBean->txnStatus)
            ];
        } else {
            $utr = ($response->payOutBean->bankRefNo == 'NA') ? null : $response->payOutBean->bankRefNo;
            $data = [
                'status' => 'failed',
                'message' => $response->response->description,
                'transaction_status' => strtolower($response->payOutBean->txnStatus),
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
        return json_decode(preg_replace('/[\x00-\x1F\x7F]/', "", $padtext));
    }

    public function initiateTransaction()
    {
        // $refernce_id = uniqid('sfx-tst');
        // $data = json_encode([
        //     'header' => [
        //         'operatingSystem' => 'WEB',
        //         'sessionId' => config('services.safexpay.merchant_id'),
        //         'version' => '1.0.0'
        //     ],
        //     'userInfo' => "{}",
        //     'transaction' => [
        //         'requestType' => 'WTW',
        //         'requestSubType' => 'PWTB',
        //         'tranCode' => 0,
        //         'txnAmt' => 0.0,
        //         'id' => config('services.safexpay.merchant_id'),
        //         'surChargeAmt' => 0,
        //         'txnCode' => 0,
        //         'userType' => 0
        //     ],
        //     'payOutBean' => [
        //         'mobileNo' => 9999999999,
        //         'txnAmount' => 10,
        //         'accountNo' => 41362834643,
        //         'ifscCode' => "SBIN0032284",
        //         'bankName' => "SBI",
        //         "txnType" => "IMPS",
        //         'accountHolderName' => "TEST",
        //         'emailId' => "john@email.com",
        //         'orderRefNo' => $refernce_id,
        //         'count' => 0
        //     ]
        // ]);
        // $encryption = $this->encrypt($data, config('services.safexpay.merchant_key'));

        // $payload = [
        //     'uId' => config('services.safexpay.merchant_id'),
        //     'payload' => $encryption
        // ];

        // $response = Http::post(config('services.safexpay.base_url'), $payload);
        $respone['payload'] = "4T4R1p81fIGaqGrXbc48+Bxr49Qdwojlx3l2NySAJvU8HrXOcro0dEZVaKfPEFhMDvC8JREGjWDgspzcpSKXIOpMMiVoa2XhXNk02dFkU+dAtrh9UyoZF8Q3/wM4z25+zAY1Bs5cgZg7kP9B4hy8vkO4EiP9x5VKB2DWO8ENaf663d759DNqakFBQurVuh4348rmY+ILr2YpDmOI1WXr/3yJUmFqmMELs12szbBPROXf99MVUkc+Vb7UTowSUcX7V2PxdlmrRMtTUdAJsFgF4nAS+5bou7dC9EWOz/BOsoZ9MCnLVTenVG9oDoRosiTB1CuIuyp0//KLluCwAi6xUkHd6jWLmJWBI/bnIemRv3kZY8mbPP4w6FHcHIe//BU19FZzCKAqiNRuRTalxfG68YZCgByas+oBbj135IOODFhiAcNmYu/zebkEE4X8l3Z1rcQqFQfEhAfO3+qsvAduXyZaVawm1zo3RKC6Ujn62RswbDtAtwUIiqb3WoiT5g3Q6BtZ5FaCej5q1VPp4pPFoPIeHg8H4i+2TjHQqpQOnWW5JGLqdcLOQTPwS/bdHpqS9tNuFSlx8JqqGxWfQMvlFnyk5I9z2zbxuE8iCslobUWzWkvNhuS6ZCM/ZozdcKRx52xJ1y0KWm7BCZgfoxQw0PPVFfULui3dqd7QZd5bjAMyZEaX9pD7nrmdXrQ3gVo4mY11Hcx/ObUHJ1hQ842WuUAPWuVm485Y0y8bSVx+++qAfB5sqg5E4TSwHCpYjA8yoLT23uOTpEe7PJT0n2FZ5Zny3MgcnUME6O0IgF7v+sOMDaTkGuatNHJGc8touxidTPMh4z7Nek+VUyjuyeL/P3DmXhfx8pMhmi967MNCDegnjJzTbKzorCzd0jYDEZCrQtt/nyz2WKt0Hy8/xc2UzNYdoAV1bFOkioZAf1M8JuefU+vWFf/6U2YkqseHk/4urUHjwrPGBfWMuQUufcEUjUPu4lcEljwN1vqcfW2/gium4U47qYY9Jo4T8Nuk5hyYGrntXepphPEl2/N0KK0Vk80fOSTzJZ1N27AEXK5WISen3qCsob2kzUE2kF0oAGbLZ/ZyhI84gf8FiCsF+5zmcbM3ATFgbGcxV4vdoJUKYNPVYC6OXGnD99gDv7UnwR2HA0V044YdgLVBefVnSepKLg9ErE0ivNu31NmM2KfKdVSksfr9blxqCjG+RBa9iFfFZOeXMqi+V6x9JAKP8uWrp4NUw1okBMR/Iozn+8H2GjaeejGJ2VebTnu8D2tU/xbxh1ZfSJpCDKtrdRWNtM2VsQGENk4NgiG3/U4jiBViMQ95hLnLZguRmVI3IBtJK1OlupEXJmMefUHbflwudfX80hXWeiY6g05NrFbVoOhRg7qMJaQMXONWTP7wRIQ3jfwXPrgL060N5faKJA211sFb+Q==";
        $decrypt = $this->decrypt($respone['payload'], config('services.safexpay.merchant_key'), config('services.safexpay.iv'));
        // return $decrypt;

        // if ($response->failed()) {
        //     $this->releaseLock($request->user()->id);
        //     abort($response->status(), "Gateway Failure!");
        // }

        return $this->processResponse($decrypt);
    }
}
