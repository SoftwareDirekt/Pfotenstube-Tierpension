<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Concerns\ApiJsonResponses;
use App\Models\CustomerAccount;
use App\Services\CustomerPortal\AccountLinkService;
use App\Services\CustomerPortal\VerificationCodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CustomerAuthController extends Controller
{
    use ApiJsonResponses;

    public function __construct(
        private readonly VerificationCodeService $verificationCodeService,
        private readonly AccountLinkService $accountLinkService
    ) {
    }

    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|max:50',
            'street' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'zipcode' => 'nullable|string|max:50',
            'country' => 'nullable|string|max:100',
            'type' => 'nullable|in:Stammkunde,Organisation',
            'privacy_accepted' => 'required|accepted',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse(
                'Validierungsfehler bei der Registrierung.',
                $validator->errors(),
                422
            );
        }

        $data = $validator->validated();

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

            // Falls kein Type geliefert wird, immer Stammkunde setzen.
            $data['type'] = $data['type'] ?? 'Stammkunde';
            $this->accountLinkService->linkOrCreateCustomerForAccount($account, $data);
            $this->verificationCodeService->issueCode($account);

            return $account;
        });

        return $this->successResponse('Registrierung gestartet. Bitte E-Mail-Code bestätigen.', [
            'account_id' => $account->id,
        ]);
    }

    public function verifyEmailCode(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:255',
            'code' => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse(
                'Validierungsfehler bei der Code-Bestätigung.',
                $validator->errors(),
                422
            );
        }

        $data = $validator->validated();

        $account = CustomerAccount::where('email', $data['email'])->first();
        if (!$account) {
            return $this->errorResponse('Ungültige Anfrage.', [
                'email' => ['Kein Konto mit dieser E-Mail gefunden.'],
            ], 422);
        }

        $isValid = $this->verificationCodeService->verifyCode($account, $data['code']);
        if (!$isValid) {
            return $this->errorResponse('Code ist ungültig oder abgelaufen.', [
                'code' => ['Der Bestätigungscode ist ungültig oder abgelaufen.'],
            ], 422);
        }

        return $this->successResponse('E-Mail wurde erfolgreich bestätigt.');
    }

    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:255',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse(
                'Validierungsfehler beim Login.',
                $validator->errors(),
                422
            );
        }

        $data = $validator->validated();

        $account = CustomerAccount::with('customer')->where('email', $data['email'])->first();
        if (!$account || !Hash::check($data['password'], $account->password)) {
            return $this->errorResponse('E-Mail oder Passwort ist ungültig.', [
                'email' => ['E-Mail oder Passwort ist ungültig.'],
            ], 422);
        }

        if (!$account->email_verified_at || $account->status !== 'active') {
            return $this->errorResponse('Bitte zuerst E-Mail bestätigen.', null, 403);
        }

        $account->last_login_at = now();
        $account->save();

        $token = $account->createToken('customer-portal')->plainTextToken;

        return $this->successResponse('Login erfolgreich.', [
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

        return $this->successResponse('Erfolgreich abgemeldet.');
    }

    public function resendCode(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:255',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse(
                'Validierungsfehler beim erneuten Code-Versand.',
                $validator->errors(),
                422
            );
        }

        $data = $validator->validated();

        $account = CustomerAccount::where('email', $data['email'])->first();
        if (!$account) {
            return $this->errorResponse('Ungültige Anfrage.', [
                'email' => ['Kein Konto mit dieser E-Mail gefunden.'],
            ], 422);
        }

        $this->verificationCodeService->issueCode($account);

        return $this->successResponse('Neuer Code wurde gesendet.');
    }

    public function me(Request $request): JsonResponse
    {
        /** @var \App\Models\CustomerAccount $account */
        $account = $request->user()->load('customer');

        return $this->successResponse('Konto erfolgreich geladen.', [
            'account' => [
                'id' => $account->id,
                'email' => $account->email,
                'status' => $account->status,
                'customer' => $account->customer,
            ],
        ]);
    }
}
