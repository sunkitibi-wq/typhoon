<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Banking\AccountType;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Auth\Events\Registered;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        if (!$user->accounts()->exists()) {
            $accountType = AccountType::where('code', 'personal')->first();
            if ($accountType) {
                $user->accounts()->create([
                    'account_type_id' => $accountType->id,
                    'account_number' => 'TY' . str_pad((string) random_int(0, 9999999999), 10, '0', STR_PAD_LEFT),
                    'currency' => 'EUR',
                    'balance' => 0,
                    'available_balance' => 0,
                    'ledger_balance' => 0,
                    'status' => 'active',
                    'label' => $accountType->name,
                    'is_default' => true,
                ]);
            }
        }

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'kyc_level' => $user->kyc_level,
                'has_account' => $user->accounts()->exists(),
            ],
        ]);
    }

    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8|confirmed',
            'phone' => 'nullable|string|max:20',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'phone' => $validated['phone'] ?? null,
            'country_of_residence' => 'DE',
        ]);

        $accountType = AccountType::where('code', 'personal')->first();
        if ($accountType) {
            $user->accounts()->create([
                'account_type_id' => $accountType->id,
                'account_number' => 'TY' . str_pad((string) random_int(0, 9999999999), 10, '0', STR_PAD_LEFT),
                'currency' => 'EUR',
                'balance' => 0,
                'available_balance' => 0,
                'ledger_balance' => 0,
                'status' => 'active',
                'label' => $accountType->name,
                'is_default' => true,
            ]);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        event(new Registered($user));

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ], 201);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load(['accounts.accountType', 'kycVerification']);

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'kyc_level' => $user->kyc_level,
                'country_of_residence' => $user->country_of_residence,
                'two_factor_enabled' => $user->two_factor_enabled,
                'accounts' => $user->accounts->map(fn($a) => [
                    'id' => $a->id,
                    'number' => $a->account_number,
                    'iban' => $a->iban,
                    'type' => $a->accountType?->name,
                    'currency' => $a->currency,
                    'balance' => $a->balance,
                    'available_balance' => $a->available_balance,
                    'status' => $a->status,
                    'is_default' => $a->is_default,
                ]),
                'kyc_status' => $user->kycVerification?->status ?? 'not_submitted',
            ],
        ]);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $code = random_int(100000, 999999);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $validated['email']],
            [
                'token' => Hash::make($code),
                'created_at' => now(),
            ]
        );

        try {
            Mail::raw("Your password reset OTP is: {$code}. This code will expire in 15 minutes.", function ($message) use ($validated) {
                $message->to($validated['email'])->subject('Password Reset OTP');
            });
        } catch (\Exception $e) {
            Log::error("Failed to send password reset email: " . $e->getMessage());
        }

        Log::info("Password reset OTP for {$validated['email']}: {$code}");

        return response()->json(['message' => 'Reset code has been sent to your email.']);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email|exists:users,email',
            'token' => 'required|string|size:6',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $record = DB::table('password_reset_tokens')->where('email', $validated['email'])->first();

        if (!$record) {
            return response()->json(['message' => 'Invalid or expired OTP.'], 422);
        }

        if (now()->subMinutes(15)->gt($record->created_at)) {
            DB::table('password_reset_tokens')->where('email', $validated['email'])->delete();
            return response()->json(['message' => 'OTP has expired.'], 422);
        }

        if (!Hash::check($validated['token'], $record->token)) {
            return response()->json(['message' => 'Invalid OTP.'], 422);
        }

        $user = User::where('email', $validated['email'])->firstOrFail();
        $user->update(['password' => Hash::make($validated['password'])]);

        DB::table('password_reset_tokens')->where('email', $validated['email'])->delete();

        return response()->json(['message' => 'Your password has been reset successfully.']);
    }
}
