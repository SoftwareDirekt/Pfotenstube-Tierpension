<?php

namespace App\Http\Controllers;

use App\Mail\PflegevertragMail;
use App\Models\BoardingCareAgreement;
use App\Models\Reservation;
use App\Services\PflegevertragPdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class PflegevertragController extends Controller
{
    public function __construct(
        private PflegevertragPdfService $pdfService
    ) {}

    public function show(int $id)
    {
        $reservation = Reservation::with(['dog.customer'])->findOrFail($id);
        $agreement = $this->agreementFor($reservation);

        // Frontend-prefill only (no DB write): if this reservation has a fresh/untouched draft,
        // show the latest previous agreement values for the same dog, but never signatures.
        if ($this->isUntouchedDraft($agreement)) {
            $latestPrevious = $this->latestPreviousAgreementForDog($reservation->dog_id, $reservation->id);
            if ($latestPrevious) {
                $agreement->besonderheiten = $latestPrevious->besonderheiten;
                $agreement->care_options = is_array($latestPrevious->care_options)
                    ? $latestPrevious->care_options
                    : BoardingCareAgreement::defaultCareOptions();
            }
        }

        return view('admin.pflegevertrag.form', [
            'reservation' => $reservation,
            'agreement' => $agreement,
        ]);
    }

    public function previewPdf(Request $request, int $id)
    {
        $reservation = Reservation::findOrFail($id);
        $agreement = $this->agreementFor($reservation);

        $prevBesonderheiten = $agreement->besonderheiten;
        $prevCare = $agreement->care_options;

        $agreement->besonderheiten = $request->input('besonderheiten', $prevBesonderheiten ?? '');
        $agreement->care_options = $this->mergeCareOptionsFromRequest($request, $prevCare ?? []);

        try {
            $binary = $this->pdfService->renderPdf($agreement);
        } finally {
            $agreement->besonderheiten = $prevBesonderheiten;
            $agreement->care_options = $prevCare;
        }

        return response($binary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="pflegevertrag-vorschau-'.$id.'.pdf"',
        ]);
    }

    public function downloadFinal(int $id)
    {
        $reservation = Reservation::with('dog')->findOrFail($id);
        $agreement = $this->agreementFor($reservation);

        if (! $agreement->final_pdf_path || ! Storage::disk('local')->exists($agreement->final_pdf_path)) {
            abort(404);
        }

        $filename = PflegevertragMail::sanitizedPdfFilename($reservation->dog?->name);

        return response()->download(
            Storage::disk('local')->path($agreement->final_pdf_path),
            $filename
        );
    }

    public function saveForm(Request $request, int $id)
    {
        $reservation = Reservation::findOrFail($id);
        $agreement = $this->agreementFor($reservation);

        if (! $agreement->canEditForm()) {
            return redirect()->route('admin.reservation.pflegevertrag.show', $id)
                ->with('error', 'Das Formular kann nach der Unterschrift bei Abgabe nicht mehr geändert werden.');
        }

        $request->validate([
            'besonderheiten' => ['nullable', 'string', 'max:5000'],
        ]);

        $agreement->besonderheiten = $request->input('besonderheiten', '');
        $agreement->care_options = $this->mergeCareOptionsFromRequest($request, $agreement->care_options ?? []);
        $agreement->save();

        return redirect()->route('admin.reservation.pflegevertrag.show', $id)
            ->with('success', 'Pflegevereinbarung gespeichert.');
    }

    public function signIntake(Request $request, int $id)
    {
        $reservation = Reservation::findOrFail($id);
        $agreement = $this->agreementFor($reservation);

        if (! $agreement->canSignIntake()) {
            return redirect()->route('admin.reservation.pflegevertrag.show', $id)
                ->with('error', 'Unterschrift bei Abgabe ist in diesem Status nicht möglich.');
        }

        $request->validate([
            'signature' => ['required', 'string', 'min:100'],
            'besonderheiten' => ['nullable', 'string', 'max:5000'],
        ]);

        if ($agreement->canEditForm()) {
            $agreement->besonderheiten = $request->input('besonderheiten', '');
            $agreement->care_options = $this->mergeCareOptionsFromRequest($request, $agreement->care_options ?? []);
        }

        $agreement->intake_signature_path = $this->storeSignaturePng($agreement, 'intake', $request->input('signature'));
        $agreement->status = BoardingCareAgreement::STATUS_INTAKE_SIGNED;
        $agreement->intake_signed_at = now();
        $agreement->save();

        return redirect()->route('admin.reservation.pflegevertrag.show', $id)
            ->with('success', 'Unterschrift bei Abgabe gespeichert.');
    }

    public function signCheckout(Request $request, int $id)
    {
        $reservation = Reservation::findOrFail($id);
        $agreement = $this->agreementFor($reservation);

        if (! $agreement->canSignCheckout()) {
            return redirect()->route('admin.reservation.pflegevertrag.show', $id)
                ->with('error', 'Abhol-Unterschrift ist derzeit nicht möglich.');
        }

        $request->validate([
            'signature' => ['required', 'string', 'min:100'],
        ]);

        DB::transaction(function () use ($agreement, $request): void {
            $agreement->checkout_signature_path = $this->storeSignaturePng($agreement, 'checkout', $request->input('signature'));
            $agreement->status = BoardingCareAgreement::STATUS_COMPLETED;
            $agreement->checkout_signed_at = now();

            $binary = $this->pdfService->renderPdf($agreement);
            $agreement->final_pdf_path = $this->pdfService->storeFinalPdf($agreement, $binary);
            $agreement->save();
        });

        return redirect()->route('admin.reservation.pflegevertrag.show', $id)
            ->with('success', 'Abholung unterschrieben. PDF wurde erzeugt.');
    }

    public function sendEmail(Request $request, int $id)
    {
        $reservation = Reservation::with('dog.customer')->findOrFail($id);
        $agreement = $this->agreementFor($reservation);

        if ($agreement->status !== BoardingCareAgreement::STATUS_COMPLETED
            || ! $agreement->final_pdf_path
            || ! Storage::disk('local')->exists($agreement->final_pdf_path)) {
            return redirect()->route('admin.reservation.pflegevertrag.show', $id)
                ->with('error', 'E-Mail Versand nur nach abgeschlossener Vereinbarung mit PDF möglich.');
        }

        $email = $reservation->dog?->customer?->email;
        if (! $email) {
            return redirect()->route('admin.reservation.pflegevertrag.show', $id)
                ->with('error', 'Keine E-Mail-Adresse beim Kunden hinterlegt.');
        }

        Mail::to($email)->send(new PflegevertragMail($agreement));

        $agreement->email_sent_at = now();
        $agreement->save();

        return redirect()->route('admin.reservation.pflegevertrag.show', $id)
            ->with('success', 'Pflegevereinbarung wurde per E-Mail versendet.');
    }

    private function agreementFor(Reservation $reservation): BoardingCareAgreement
    {
        $agreement = BoardingCareAgreement::firstOrCreate(
            ['reservation_id' => $reservation->id],
            [
                'status' => BoardingCareAgreement::STATUS_DRAFT,
                'care_options' => BoardingCareAgreement::defaultCareOptions(),
            ]
        );

        if ($agreement->care_options === null) {
            $agreement->care_options = BoardingCareAgreement::defaultCareOptions();
            $agreement->save();
        }

        return $agreement;
    }

    private function isUntouchedDraft(BoardingCareAgreement $agreement): bool
    {
        if ($agreement->status !== BoardingCareAgreement::STATUS_DRAFT) {
            return false;
        }

        $noSignatures = ! $agreement->intake_signature_path
            && ! $agreement->checkout_signature_path
            && ! $agreement->intake_signed_at
            && ! $agreement->checkout_signed_at
            && ! $agreement->final_pdf_path
            && ! $agreement->email_sent_at;

        if (! $noSignatures) {
            return false;
        }

        $isEmptyText = trim((string) ($agreement->besonderheiten ?? '')) === '';
        $hasAnyCareData = $this->hasAnyCareData(is_array($agreement->care_options) ? $agreement->care_options : []);

        return $isEmptyText && ! $hasAnyCareData;
    }

    /**
     * Most recently *completed* agreement for this dog (any other reservation), by checkout time — not by stay check-in date,
     * so a newer contract for an earlier-dated stay still wins over an older contract for a later-dated stay.
     */
    private function latestPreviousAgreementForDog(int $dogId, int $currentReservationId): ?BoardingCareAgreement
    {
        return BoardingCareAgreement::query()
            ->where('status', BoardingCareAgreement::STATUS_COMPLETED)
            ->whereHas('reservation', function ($query) use ($dogId, $currentReservationId): void {
                $query->where('dog_id', $dogId)
                    ->where('id', '!=', $currentReservationId);
            })
            ->orderByDesc('checkout_signed_at')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Returns true if care options contain any user-entered selection.
     *
     * @param  array<string, mixed>  $care
     */
    private function hasAnyCareData(array $care): bool
    {
        $futter = (array) ($care['futter'] ?? []);
        foreach (['dosenfutter', 'trockenfutter', 'fleisch', 'diaet'] as $k) {
            $freq = (int) data_get($futter, $k.'.freq', 0);
            if (in_array($freq, [1, 2, 3], true)) {
                return true;
            }
        }

        $bad = (array) ($care['bad'] ?? []);
        if (! empty($bad['bei_abholung']) || ! empty($bad['einmal_woche']) || ! empty($bad['schur'])) {
            return true;
        }

        $med = (array) ($care['medikamente'] ?? []);
        $medItems = $med['items'] ?? null;
        if (is_array($medItems) && $medItems !== []) {
            foreach ($medItems as $it) {
                if (! is_array($it)) {
                    continue;
                }
                $n = trim((string) ($it['note'] ?? ''));
                $f = (int) ($it['freq'] ?? 0);
                if (in_array($f, [1, 2, 3], true) || $n !== '') {
                    return true;
                }
            }
        } else {
            $medFreq = (int) ($med['freq'] ?? 0);
            $medNote = trim((string) ($med['note'] ?? ''));
            if (in_array($medFreq, [1, 2, 3], true) || $medNote !== '') {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>|null  $current
     * @return array<string, mixed>
     */
    private function mergeCareOptionsFromRequest(Request $request, ?array $current): array
    {
        $merged = array_replace_recursive(
            BoardingCareAgreement::defaultCareOptions(),
            is_array($current) ? $current : []
        );

        foreach (['dosenfutter', 'trockenfutter', 'fleisch', 'diaet'] as $k) {
            $freq = (int) $request->input('futter_'.$k.'_freq', 0);
            $merged['futter'][$k]['freq'] = in_array($freq, [1, 2, 3], true) ? $freq : null;
            $merged['futter'][$k]['on'] = $merged['futter'][$k]['freq'] !== null;
        }

        $badChoice = (string) $request->input('bad_choice', '');
        foreach (['bei_abholung', 'einmal_woche', 'schur'] as $k) {
            $merged['bad'][$k] = $badChoice === $k;
        }

        $items = [];
        $raw = $request->input('med_items', []);
        if (is_array($raw)) {
            foreach ($raw as $row) {
                if (! is_array($row)) {
                    continue;
                }
                $note = trim((string) ($row['note'] ?? ''));
                $f = (int) ($row['freq'] ?? 0);
                $f = in_array($f, [1, 2, 3], true) ? $f : null;
                if ($note === '' && $f === null) {
                    continue;
                }
                $items[] = [
                    'note' => $note,
                    'freq' => $f,
                ];
            }
        }
        $merged['medikamente']['items'] = $items;
        $merged['medikamente']['note'] = '';
        $merged['medikamente']['freq'] = null;
        $merged['medikamente']['on'] = $items !== [];

        return $merged;
    }

    private function storeSignaturePng(BoardingCareAgreement $agreement, string $basename, string $payload): string
    {
        if (preg_match('/^data:image\/png;base64,(.+)$/is', $payload, $m)) {
            $raw = base64_decode($m[1], true);
        } else {
            $raw = base64_decode($payload, true);
        }

        if ($raw === false || strlen($raw) < 80) {
            throw ValidationException::withMessages([
                'signature' => 'Ungültige oder zu kleine Signatur.',
            ]);
        }

        $dir = 'pflegevertraege/'.$agreement->id;
        Storage::disk('local')->makeDirectory($dir);
        $relative = $dir.'/'.$basename.'.png';
        Storage::disk('local')->put($relative, $raw);

        return $relative;
    }
}
