<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request): array
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['sometimes', 'string', 'max:255'],
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Email atau password salah.'],
            ]);
        }

        return [
            'token' => $user->createToken($request->input('device_name', 'api'))->plainTextToken,
            'user' => new UserResource($user),
        ];
    }

    public function me(Request $request): UserResource
    {
        return new UserResource($request->user());
    }

    public function modelSerializationDemo(Request $request): array
    {
        $user = $request->user();
        $rawFields = array_keys($user->getAttributes());
        $sensitiveFields = array_values(array_intersect(
            ['password', 'remember_token'],
            $rawFields
        ));

        return [
            'unsafe_model' => [
                'fields_found' => $rawFields,
                'sensitive_fields_that_must_not_be_returned' => $sensitiveFields,
                'example' => 'Mengirim model mentah dapat membocorkan nilai field sensitif tersebut.',
            ],
            'safe_resource' => (new UserResource($user))->resolve($request),
        ];
    }

    public function logout(Request $request): \Illuminate\Http\Response
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->noContent();
    }
}
