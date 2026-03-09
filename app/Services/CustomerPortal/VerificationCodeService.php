<?php

namespace App\Services\CustomerPortal;

use App\Models\CustomerAccount;
use App\Models\CustomerVerificationCode;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class VerificationCodeService
{
    private const MAX_ATTEMPTS = 5;

    public function issueCode(CustomerAccount $account, int $ttlMinutes = 15): string
    {
        $plainCode = (string) random_int(100000, 999999);

        $account->verificationCodes()
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->update(['used_at' => now()]);

        CustomerVerificationCode::create([
            'customer_account_id' => $account->id,
            'code_hash' => Hash::make($plainCode),
            'expires_at' => now()->addMinutes($ttlMinutes),
            'attempts' => 0,
        ]);

        Mail::raw(
            "Dein Verifizierungscode lautet: {$plainCode}\n\nDer Code ist {$ttlMinutes} Minuten gueltig.",
            static function ($message) use ($account): void {
                $message->to($account->email)->subject('Dein Verifizierungscode');
            }
        );

        return $plainCode;
    }

    public function verifyCode(CustomerAccount $account, string $inputCode): bool
    {
        $code = $account->verificationCodes()
            ->whereNull('used_at')
            ->orderByDesc('id')
            ->first();

        if (!$code) {
            return false;
        }

        if (Carbon::parse($code->expires_at)->isPast()) {
            return false;
        }

        if ($code->attempts >= self::MAX_ATTEMPTS) {
            return false;
        }

        $code->attempts++;
        $code->save();

        if (!Hash::check($inputCode, $code->code_hash)) {
            return false;
        }

        $code->used_at = now();
        $code->save();

        $account->email_verified_at = now();
        $account->status = 'active';
        $account->save();

        return true;
    }
}
