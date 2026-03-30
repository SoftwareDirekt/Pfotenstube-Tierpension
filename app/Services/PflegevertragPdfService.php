<?php

namespace App\Services;

use App\Models\BoardingCareAgreement;
use App\Models\Reservation;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class PflegevertragPdfService
{
    public function stayDurationDays(Reservation $reservation): int
    {
        $checkin = Carbon::parse($reservation->checkin_date)->startOfDay();
        $checkout = Carbon::parse($reservation->checkout_date)->startOfDay();
        $daysDiff = $checkin->diffInDays($checkout);

        if ($daysDiff === 0) {
            return 1;
        }

        $mode = config('app.days_calculation_mode', 'inclusive');

        return $mode === 'inclusive' ? (int) $daysDiff + 1 : (int) $daysDiff;
    }

    /**
     * @return array<string, mixed>
     */
    public function viewData(BoardingCareAgreement $agreement): array
    {
        $reservation = $agreement->reservation()->with(['dog.customer', 'plan'])->firstOrFail();
        $dog = $reservation->dog;
        $customer = $dog->customer;

        $checkin = Carbon::parse($reservation->checkin_date);
        $checkout = Carbon::parse($reservation->checkout_date);

        $intakeSig = $this->signatureDataUri($agreement->intake_signature_path);
        $checkoutSig = $this->signatureDataUri($agreement->checkout_signature_path);

        return [
            'agreement' => $agreement,
            'reservation' => $reservation,
            'dog' => $dog,
            'customer' => $customer,
            'org' => $this->organizationForPdf(),
            'checkin_formatted' => $checkin->format('d.m.Y'),
            'checkout_formatted' => $checkout->format('d.m.Y'),
            'duration_days' => $this->stayDurationDays($reservation),
            'care' => $agreement->care_options,
            'intake_signature_data_uri' => $intakeSig,
            'checkout_signature_data_uri' => $checkoutSig,
            'location_line' => 'Bruck an der Leitha',
            'date_today' => Carbon::now()->format('d.m.Y'),
        ];
    }

    /**
     * Organisation block from the admin user record (Einstellungen / Firmendaten) — same fields as Rechnungen.
     *
     * @return array{name: string, address_line: string, phone: string, email: string, role_line: string, signature_data_uri: string|null}
     */
    private function organizationForPdf(): array
    {
        $admin = User::query()->where('role', 1)->orderBy('id')->first();

        if (! $admin) {
            return [
                'name' => '',
                'address_line' => '',
                'phone' => '',
                'email' => '',
                'role_line' => 'Pfleger des Tieres',
                'signature_data_uri' => null,
            ];
        }

        $addressLine = implode(', ', array_filter([
            $admin->address,
            $admin->city,
            $admin->country,
        ]));

        return [
            'name' => (string) ($admin->company_name ?? ''),
            'address_line' => $addressLine,
            'phone' => (string) ($admin->phone ?? ''),
            'email' => (string) ($admin->company_email ?? ''),
            'role_line' => 'Pfleger des Tieres',
            'signature_data_uri' => $this->publicStorageImageDataUri($admin->signature ?? null),
        ];
    }

    private function publicStorageImageDataUri(?string $relativePath): ?string
    {
        if (! $relativePath) {
            return null;
        }

        $normalized = ltrim(str_replace('\\', '/', $relativePath), '/');
        if (! Storage::disk('public')->exists($normalized)) {
            return null;
        }

        $binary = Storage::disk('public')->get($normalized);
        if ($binary === false || $binary === '') {
            return null;
        }

        $ext = strtolower(pathinfo($normalized, PATHINFO_EXTENSION));
        $mime = match ($ext) {
            'jpg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            default => 'image/png',
        };

        return 'data:'.$mime.';base64,'.base64_encode($binary);
    }

    private function signatureDataUri(?string $relativePath): ?string
    {
        if (! $relativePath || ! Storage::disk('local')->exists($relativePath)) {
            return null;
        }

        $binary = Storage::disk('local')->get($relativePath);
        if ($binary === '' || $binary === false) {
            return null;
        }

        return 'data:image/png;base64,'.base64_encode($binary);
    }

    public function renderPdf(BoardingCareAgreement $agreement): string
    {
        $data = $this->viewData($agreement);

        $pdf = Pdf::loadView('pdf.pflegevertrag', $data)
            ->setPaper('a4', 'portrait')
            ->setOption('defaultFont', 'DejaVu Sans');

        return $pdf->output();
    }

    public function storeFinalPdf(BoardingCareAgreement $agreement, string $binary): string
    {
        $relative = 'pflegevertraege/'.$agreement->id.'/final.pdf';
        Storage::disk('local')->put($relative, $binary);

        return $relative;
    }
}
