<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Otp;
use Firebase\JWT\JWT;
use Illuminate\Cache\Lock;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use App\Http\Resources\GeneralResource;
use App\Models\Payout;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Ramsey\Uuid\UuidInterface;

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

    public function paydeerToken(): Response
    {
        return Http::post(config('services.paydeer.base_url') . '/API/public/api/v1.1/generateToken', ['clientKey' => config('services.paydeer.key'), 'clientSecret' => config('services.paydeer.secret')]);
    }

    public function triggerSms(Request $request, array $contents)
    {
        $user = $request->user();
        $link = env('FRONTEND_URL');
        $phone = $user->phone_number;
        $password = Str::random(8);
        $this->storeOtp($password, 'phone_login');
        $text = `Hello {$user->name}, Welcome to . Visit {$link}/login to start your transaction use Login Id : {$user->email} and Password : $password. -From PESA24 TECHNOLOGY PRIVATE LIMITED`;
        $otp =  Http::post("http://alerts.prioritysms.com/api/web2sms.php?workingkey=Ab6a47904876c763b307982047f84bb80&to=$phone&sender=PTECHP&message=$text", []);
    }

    public function lockRecords($key): Lock
    {
        return Cache::lock($key, 30);
    }

    public function releaseLock($key): bool
    {
        return Cache::lock($key)->release();
    }

    public function storeOtp(string $password, string $intent): JsonResource
    {
        $data = Otp::create([
            'user_id' => auth()->user()->id,
            'password' => Hash::make($password),
            'intent' => $intent,
            'expiry_at' => Carbon::now()->addMinutes(5)
        ]);

        return new GeneralResource($data);
    }

    public function generateIdempotentKey(): string
    {
        return once(function () {
            return (string) Str::uuid();
        });
    }
}
