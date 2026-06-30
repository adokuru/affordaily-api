<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PasswordResetOtp;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    /**
     * Login user and create token
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        if ($this->usesProductionDemoCredentials($request)) {
            return response()->json([
                'success' => false,
                'message' => 'Default demo credentials are disabled in production',
            ], 401);
        }

        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials'
            ], 401);
        }

        $user = User::where('email', $request->email)->firstOrFail();
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'data' => [
                'user' => $user,
                'access_token' => $token,
                'token_type' => 'Bearer',
            ]
        ]);
    }

    private function usesProductionDemoCredentials(Request $request): bool
    {
        if (config('app.env') !== 'production') {
            return false;
        }

        return match ($request->email) {
            'admin@affordaily.com' => $request->password === 'admin123',
            'receptionist@affordaily.com' => $request->password === 'receptionist123',
            default => false,
        };
    }

    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $email = strtolower($request->email);
        $user = User::where('email', $email)->first();

        if ($user) {
            PasswordResetOtp::where('email', $email)
                ->whereNull('consumed_at')
                ->update(['consumed_at' => now()]);

            $otp = (string) random_int(100000, 999999);

            PasswordResetOtp::create([
                'email' => $email,
                'otp_hash' => Hash::make($otp),
                'expires_at' => now()->addMinutes(10),
            ]);

            Mail::raw(
                "Your Affordaily password reset code is {$otp}. It expires in 10 minutes.",
                fn ($message) => $message
                    ->to($user->email)
                    ->subject('Affordaily password reset code')
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'If that email exists, a reset code has been sent.',
        ]);
    }

    public function verifyPasswordOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'otp' => 'required|digits:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $otp = $this->validPasswordResetOtp(strtolower($request->email), $request->otp);

        if (! $otp) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired reset code',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Reset code verified',
        ]);
    }

    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'otp' => 'required|digits:6',
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $email = strtolower($request->email);
        $otp = $this->validPasswordResetOtp($email, $request->otp);

        if (! $otp) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired reset code',
            ], 422);
        }

        $user = User::where('email', $email)->first();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired reset code',
            ], 422);
        }

        $user->forceFill([
            'password' => $request->password,
        ])->save();

        $otp->update(['consumed_at' => now()]);
        $user->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Password reset successful',
        ]);
    }

    private function validPasswordResetOtp(string $email, string $otp): ?PasswordResetOtp
    {
        $resetOtp = PasswordResetOtp::active()
            ->where('email', $email)
            ->latest()
            ->first();

        if (! $resetOtp || $resetOtp->attempts >= 5) {
            return null;
        }

        if (! Hash::check($otp, $resetOtp->otp_hash)) {
            $resetOtp->increment('attempts');

            return null;
        }

        return $resetOtp;
    }

    /**
     * Logout user (Revoke the token)
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Successfully logged out'
        ]);
    }
}
