<?php

namespace App\Http\Controllers\Services\Payout;

use Carbon\Carbon;
use App\Models\Payout;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\PayoutRequest;
use App\Http\Resources\GeneralResource;
use Illuminate\Http\Exceptions\HttpResponseException;

class FlowController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $data = Payout::where('user_id', $request->user()->id)
            ->whereBetween('created_at', [$request->from ?? Carbon::now()->startOfDay(), $request->to ?? Carbon::now()->endOfDay()])
            ->paginate(30);

        return GeneralResource::collection($data);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PayoutRequest $request)
    {
        $lock = $this->lockRecords($request->user()->id);
        if (!$lock->get()) {
            throw new HttpResponseException(response()->json(['message' => "Failed to acquire lock"], 423));
        }

        $class_name = Str::of($request->provider . "_" . "controller")->studly();
        $class = __NAMESPACE__ . "\\" . $class_name;
        $instance = new $class;
        if (!class_exists($class)) {
            abort(501, 'Provider not supported');
            $lock->release();
        }

        $transaction = $instance->initiateTransaction($request);

        if ($transaction['metadata']['status'] != 'success') {
            abort(400, ['data' => ['message' => $transaction['metadata']['message']]]);
            $lock->release();
        }

        $payout = Payout::create([
            'user_id' => $request->user()->id,
            'provider' => $request->provider,
            'reference_id' => uniqid('PYT'),
            'account_number' => $request->account_number,
            'ifsc_code' => $request->ifsc_code,
            'beneficiary_name' => $request->beneficiary_name,
            'mode' => $request->mode,
            'status' => $transaction['metadata']['status'],
            'description' => $transaction['metadata']['message'],
            'metadata' => json_encode($transaction['response']),
            'remarks' => $request->remarks
        ]);

        $lock->release();

        return new GeneralResource($payout);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
