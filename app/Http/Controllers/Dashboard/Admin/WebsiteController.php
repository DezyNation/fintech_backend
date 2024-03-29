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
        return GeneralResource::collection(Service::all());
    }

    public function updateService(Request $request, Service $service): JsonResource
    {
        $service->update([
            'active' => $request->active ?? $service->active,
            'api' => $request->api ?? $service->api
        ]);

        return new GeneralResource($service);
    }

    public function storeService(Request $request): JsonResource
    {
        $request->validate([
            'name' => ['required', 'string'],
            'active' => ['required', 'boolean'],
            'description' => ['required', 'string'],
            'api' => ['required', 'boolean'],
            'provider' => ['required', 'boolean']
        ]);

        $service = Service::create([
            'name' => $request->name,
            'description' => $request->description,
            'active' => $request->active,
            'api' => $request->api,
            'provider' => $request->provider
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
            'keys' => $request->keys,
        ]);

        return new GeneralResource($data);
    }

    public function updateCredentials(Request $request, Credential $credential): JsonResource
    {
        $credential->update([
            'provider' => $request->provider ?? $credential->provider,
            'keys' => $request->keys ?? $credential->keys,
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
