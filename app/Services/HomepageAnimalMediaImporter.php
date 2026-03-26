<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HomepageAnimalMediaImporter
{
    /**
     * Store raw bytes from base64 (preferred when Homepage cannot serve HTTP during sync).
     *
     * @param  'profile'|'vaccine'  $kind
     */
    public function importFromBase64(?string $base64, ?string $pathHint, string $kind = 'profile'): ?string
    {
        if ($base64 === null || trim($base64) === '') {
            return null;
        }

        $binary = base64_decode($base64, true);
        if ($binary === false || $binary === '') {
            Log::warning('Homepage media import: invalid base64 payload', ['hint' => $pathHint]);

            return null;
        }

        $ext = strtolower(pathinfo((string) $pathHint, PATHINFO_EXTENSION));
        if (! in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf'], true)) {
            $ext = 'jpg';
        }

        try {
            $filename = uniqid('hp_', true).'.'.$ext;
            $subdir = $kind === 'vaccine' ? 'vaccine_hp' : '';
            $base = public_path('uploads/users/dogs'.($subdir ? '/'.$subdir : ''));
            if (! is_dir($base)) {
                mkdir($base, 0755, true);
            }
            file_put_contents($base.'/'.$filename, $binary);

            return $subdir ? $subdir.'/'.$filename : $filename;
        } catch (\Throwable $e) {
            Log::warning('Homepage media base64 import exception: '.$e->getMessage(), ['hint' => $pathHint]);

            return null;
        }
    }

    /**
     * Download from a public Homepage URL (fallback when base64 is not sent).
     *
     * @param  'profile'|'vaccine'  $kind
     */
    public function importFromUrl(?string $absoluteUrl, string $kind = 'profile'): ?string
    {
        $url = $this->resolveFetchableUrl($absoluteUrl);
        if (! $url) {
            return null;
        }

        try {
            $response = Http::timeout(45)
                ->withHeaders([
                    'User-Agent' => 'Pfotenstube-Tierpension-Sync/1.0',
                    'Accept' => '*/*',
                ])
                ->get($url);
            if (! $response->successful()) {
                Log::warning('Homepage media import failed', [
                    'url' => $url,
                    'status' => $response->status(),
                ]);

                return null;
            }

            $pathPart = parse_url($url, PHP_URL_PATH) ?? '';
            $ext = strtolower(pathinfo($pathPart, PATHINFO_EXTENSION));
            if (! in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf'], true)) {
                $ext = 'jpg';
            }

            $filename = uniqid('hp_', true).'.'.$ext;
            $subdir = $kind === 'vaccine' ? 'vaccine_hp' : '';
            $base = public_path('uploads/users/dogs'.($subdir ? '/'.$subdir : ''));
            if (! is_dir($base)) {
                mkdir($base, 0755, true);
            }
            file_put_contents($base.'/'.$filename, $response->body());

            return $subdir ? $subdir.'/'.$filename : $filename;
        } catch (\Throwable $e) {
            Log::warning('Homepage media import exception: '.$e->getMessage(), ['url' => $url ?? $absoluteUrl]);

            return null;
        }
    }

    /**
     * Tierpension must GET an absolute URL. Relative /storage/... needs PFOTENSTUBE_HOMEPAGE_URL.
     * In Docker, set that env to a hostname the Tierpension container can reach (e.g. host.docker.internal).
     */
    private function resolveFetchableUrl(?string $raw): ?string
    {
        if ($raw === null || trim($raw) === '') {
            return null;
        }

        $raw = trim($raw);

        if (str_starts_with($raw, 'http://') || str_starts_with($raw, 'https://')) {
            return $raw;
        }

        if (str_starts_with($raw, '/')) {
            $base = rtrim((string) config('services.pfotenstube.homepage_url'), '/');
            if ($base === '') {
                Log::warning('Homepage media import: relative URL received but PFOTENSTUBE_HOMEPAGE_URL is empty.', [
                    'url' => $raw,
                ]);

                return null;
            }

            return $base.$raw;
        }

        return $raw;
    }
}
