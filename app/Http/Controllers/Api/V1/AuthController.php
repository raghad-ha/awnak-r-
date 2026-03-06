<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use App\Models\User;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->validate([
            'name'     => ['required','string','max:255'],
            'email'    => ['required','email','max:255','unique:users,email'],
            'phone'    => ['nullable','string','max:30','unique:users,phone'],
            'password' => ['required','string','min:8','confirmed'],
            'type'     => ['required', Rule::in(['volunteer','organization'])],
        ]);

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'phone'    => $data['phone'] ?? null,
            'password' => Hash::make($data['password']),
            'type'     => $data['type'],
            'status'   => 'pending', // important: approval flow later
        ]);

        // Optional: auto-create token on register (some teams prefer login after register)
        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Registered successfully. Account is pending approval.',
            'data' => [
                'token' => $token,
                'user'  => $this->userPayload($user),
            ],
        ], 201);
    }

    public function login(Request $request)
    {
        
        $data = $request->validate([
            'login'    => ['required','string'], // email or phone
            'password' => ['required','string'],
        ]);

        $user = User::query()
            ->where('email', $data['login'])
            ->orWhere('phone', $data['login'])
            ->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'login' => ['Invalid credentials.'],
            ]);
        }

        // Block access if not approved
        if ($user->status !== 'approved') {
            return response()->json([
                'success' => false,
                'message' => match ($user->status) {
                    'pending'  => 'Account pending approval.',
                    'rejected' => 'Account rejected.',
                    'blocked'  => 'Account blocked.',
                    default    => 'Account not allowed.',
                },
                'data' => [
                    'status' => $user->status,
                ],
            ], 403);
        }

        // Optional: revoke old tokens so user has 1 active session
        $user->tokens()->delete();

        $token = $user->createToken('api')->plainTextToken;
        

        return response()->json([
            'success' => true,
            'message' => 'Login successful.',
            'data' => [
                'token' => $token,
                'user'  => $this->userPayload($user),
            ],
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out.',
            'data' => null,
        ]);
    }

    public function me(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'message' => '',
            'data' => [
                'user' => $this->userPayload($user),
            ],
        ]);
    }

    private function userPayload(User $user): array
    {
        return [
            'id'     => $user->id,
            'name'   => $user->name,
            'email'  => $user->email,
            'phone'  => $user->phone,
            'type'   => $user->type,    // volunteer | organization | staff
            'status' => $user->status,  // pending | approved | rejected | blocked
            // roles (if you use roles table): you can fill this later
            // 'roles' => $user->roles()->pluck('name'),
        ];
    }
}
