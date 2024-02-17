<?php

namespace App\Http\Controllers\Dashboard\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\GeneralResource;
use App\Models\Broadcast;
use App\Models\Credential;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WebsiteController extends Controller
{
    public function services(): JsonResource
    {
        return new GeneralResource(Service::all());
    }

    public function updateService(Request $request, Service $service): JsonResource
    {
        $service->update([
            'active' => $request->active ?? $service->active,
            'api' => $request->api ?? $service->api
        ]);

        return new GeneralResource($service);
    }

    public function storeService(Request $request, Service $service): JsonResource
    {
        $service->update([
            'active' => $request->active ?? $service->active,
            'api' => $request->api ?? $service->api
        ]);

        return new GeneralResource($service);
    }

    public function credentials(): JsonResource
    {
        return new GeneralResource(Credential::all());
    }

    public function storeCredentials(Request $request): JsonResource
    {
        $data = Credential::create([
            'provider' => $request->provider,
            'key' => $request->key,
            'secret' => $request->secret
        ]);

        return new GeneralResource($data);
    }

    public function updateCredentials(Request $request, Credential $credential): JsonResource
    {
        $credential->update([
            'provider' => $request->provider ?? $credential->provider,
            'key' => $request->key ?? $credential->key,
            'secret' => $request->secret ?? $credential->secret
        ]);

        return new GeneralResource($credential);
    }

    public function broadcasts(): JsonResource
    {
        return new GeneralResource(Broadcast::all());
    }

    public function storeBroadcast(Request $request): JsonResource
    {
        $data = Broadcast::create([
            'message' => $request->message
        ]);

        return new GeneralResource($data);
    }

    public function updateBroadcast(Request $request, Broadcast $broadcast): JsonResource
    {
        $broadcast->update([
            'message' => $request->message ?? $broadcast->message
        ]);

        return new GeneralResource($broadcast);
    }
}
