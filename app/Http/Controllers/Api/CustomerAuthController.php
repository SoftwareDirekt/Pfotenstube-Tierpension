<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CustomerAccount;
use App\Services\CustomerPortal\AccountLinkService;
use App\Services\CustomerPortal\VerificationCodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CustomerAuthController extends Controller
{
    public function __construct(
        private readonly VerificationCodeService $verificationCodeService,
        private readonly AccountLinkService $accountLinkService
    ) {
    }

    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'password' => 'required|string|min:10|confirmed',
            'phone' => 'nullable|string|max:50',
            'street' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'zipcode' => 'nullable|string|max:50',
            'country' => 'nullable|string|max:100',
            'type' => 'nullable|in:Stammkunde,Organisation',
            'privacy_accepted' => 'required|accepted',
        ]);

        $account = DB::transaction(function () use ($data) {
            $account = CustomerAccount::where('email', $data['email'])->first();

            if ($account) {
                $account->password = $data['password'];
                $account->status = 'pending';
                $account->email_verified_at = null;
                $account->save();
            } else {
                $account = CustomerAccount::create([
                    'email' => $data['email'],
                    'password' => $data['password'],
                    'status' => 'pending',
                ]);
            }

            $this->accountLinkService->linkOrCreateCustomerForAccount($account, $data);
            $this->verificationCodeService->issueCode($account);

            return $account;
        });

        return response()->json([
            'success' => true,
            'message' => 'Registrierung gestartet. Bitte E-Mail-Code bestaetigen.',
            'account_id' => $account->id,
        ]);
    }

    public function verifyEmailCode(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => 'required|email|max:255',
            'code' => 'required|string|size:6',
        ]);

        $account = CustomerAccount::where('email', $data['email'])->first();
        if (!$account) {
            return response()->json([
                'success' => false,
                'message' => 'Ungueltige Anfrage.',
            ], 422);
        }

        $isValid = $this->verificationCodeService->verifyCode($account, $data['code']);
        if (!$isValid) {
            return response()->json([
                'success' => false,
                'message' => 'Code ist ungueltig oder abgelaufen.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'E-Mail wurde erfolgreich bestaetigt.',
        ]);
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => 'required|email|max:255',
            'password' => 'required|string',
        ]);

        $account = CustomerAccount::with('customer')->where('email', $data['email'])->first();
        if (!$account || !Hash::check($data['password'], $account->password)) {
            return response()->json([
                'success' => false,
                'message' => 'E-Mail oder Passwort ist ungueltig.',
            ], 422);
        }

        if (!$account->email_verified_at || $account->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Bitte zuerst E-Mail bestaetigen.',
            ], 403);
        }

        $account->last_login_at = now();
        $account->save();

        $token = $account->createToken('customer-portal')->plainTextToken;

        return response()->json([
            'success' => true,
            'token' => $token,
            'account' => [
                'id' => $account->id,
                'email' => $account->email,
                'customer_id' => $account->customer_id,
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json([
            'success' => true,
            'message' => 'Erfolgreich abgemeldet.',
        ]);
    }

    public function resendCode(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => 'required|email|max:255',
        ]);

        $account = CustomerAccount::where('email', $data['email'])->first();
        if (!$account) {
            return response()->json([
                'success' => false,
                'message' => 'Ungueltige Anfrage.',
            ], 422);
        }

        $this->verificationCodeService->issueCode($account);

        return response()->json([
            'success' => true,
            'message' => 'Neuer Code wurde gesendet.',
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        /** @var \App\Models\CustomerAccount $account */
        $account = $request->user()->load('customer');

        return response()->json([
            'success' => true,
            'account' => [
                'id' => $account->id,
                'email' => $account->email,
                'status' => $account->status,
                'customer' => $account->customer,
            ],
        ]);
    }
}
