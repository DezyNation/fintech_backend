<?php

namespace App\Http\Controllers\Dashboard\Admin;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use App\Http\Resources\GeneralResource;
use App\Mail\SendPassword;
use App\Models\Document;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        return new GeneralResource(User::role($request->role)->with(['plan' => function ($q) {
            $q->select(['id', 'name']);
        }, 'documents'])->withTrashed()->paginate(10));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email', 'unique:users'],
            'first_name' => ['nullable', 'string', 'max:20'],
            'middle_name' => ['nullable', 'string', 'max:20'],
            'last_name' => ['nullable', 'string', 'max:20'],
            'phone_nmber' => ['nullable', 'digits:10'],
            'email' => ['required', 'email', 'unique:users']
        ]);

        $password = Str::random(8);

        User::create([
            'first_name' => $request->first_name,
            'middle_name' => $request->middle_name,
            'last_name' => $request->last_name,
            'name' => $request->first_name . $request->middle_name . $request->last_name,
            'email' => $request->email,
            'password' => Hash::make($password)
        ]);

        Mail::to($request->email)
            ->send(new SendPassword($password, 'password'));

        return response()->json(['data' => 'credentials sent.']);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $user = User::with(['documents', 'roles' =>  function ($role) {
            $role->select('name', 'id');
        }, 'permissions' => function ($permission) {
            $permission->select('id', 'name');
        }])->findOrFail($id);
        return new GeneralResource($user);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        $user->update([
            'first_name' => $request->first_name,
            'middle_name' => $request->middle_name,
            'last_name' => $request->last_name,
            'name' => Str::squish($request->first_name . ' ' . $request->middle_name . ' ' . $request->last_name),
            'phone_number' => $request->phone_number,
            'email' => $request->email,
            'admin_remarks' => $request->admin_remarks ?? $user->admin_remarks,
            'plan_id' => $request->plan_id,
            'capped_balance' => $request->capped_balance
        ]);

        return new GeneralResource($user);
    }

    public function uploadDocument(Request $request, User $user)
    {
        $request->validate([
            'document_type' => ['required', 'string', 'max:30'],
            'file' => ['required', 'mimes:jpeg,png,jpg,pdf', 'max:2048']
        ]);

        Document::updateOrInsert(
            [
                'user_id' => $user->id,
                'document_type' => $request->document_type
            ],
            [
                'address' => $request->file('file')->store("users/{$request->document_type}"),
                'updated_at' => now(),
                'created_at' => now()
            ]
        );

        return new GeneralResource($user);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        $user->delete();
        return response()->noContent();
    }

    public function restore(string $id)
    {
        User::withTrashed()->findOrFail($id)->restore();
        return response()->noContent();
    }

    public function sendCredential(Request $request, User $user): JsonResource
    {
        $request->validate([
            'channel' => ['required', 'in:email,sms'],
            'credential_type' => ['required', 'in:password,pin']
        ]);

        if ($request->channel == 'email') {
            return $this->emailCredentials($request, $user);
        } else {
            return $this->smsCredentials($request, $user);
        }
    }

    public function emailCredentials(Request $request, User $user): JsonResource
    {
        if ($request->credential_type == 'password') {
            $password = Str::random(8);
        } else {
            $password = rand(100001, 999999);
        }
        $user->update([
            $request->credential_type => Hash::make($password)
        ]);

        Mail::to($user->email)
            ->send(new SendPassword($password, $request->credential_type));

        return new GeneralResource($user);
    }

    public function smsCredentials(Request $request, User $user): JsonResource
    {
        $password = Str::random(8);
        $user->update([
            $request->credential_type => Hash::make($password)
        ]);

        return new GeneralResource($user);
    }

    public function downloadDocument(string $path)
    {
        return Storage::download($path);
    }
}
