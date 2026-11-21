<?php

namespace App\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GeminiChart
{
    private const URL = 'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent';

    public function baca(string $pathGambar, string $mime, ?string $kode, string $rentang): array
    {
        $kunci = config('services.gemini.key');
        if (! $kunci) {
            throw new RuntimeException('GEMINI_API_KEY belum diisi di file .env.');
        }

        $prompt = strtr(file_get_contents(resource_path('prompts/baca-chart.txt')), [
            '{kode}' => $kode ?: 'tidak diisi',
            '{rentang}' => $rentang,
        ]);

        $isi = [
            'contents' => [[
                'parts' => [
                    ['text' => $prompt],
                    ['inline_data' => ['mime_type' => $mime, 'data' => base64_encode(file_get_contents($pathGambar))]],
                ],
            ]],
            'generationConfig' => [
                'temperature' => 0.2,
                'responseMimeType' => 'application/json',
            ],
        ];

        $daftarModel = array_values(array_unique(array_filter([
            config('services.gemini.model'),
            config('services.gemini.model_cadangan'),
        ])));

        $galatTerakhir = null;
        $kodeTerakhir = 0;
        foreach ($daftarModel as $model) {
            try {
                $respon = $this->http()
                    ->withHeaders(['x-goog-api-key' => $kunci])
                    ->post(sprintf(self::URL, $model), $isi)
                    ->throw();

                $teks = $respon->json('candidates.0.content.parts.0.text');
                if (! $teks) {
                    throw new RuntimeException('Gemini tidak mengirim jawaban (mungkin gambar diblokir filter keamanan).');
                }

                return ['hasil' => $this->rapikan($teks), 'model' => $model];
            } catch (RequestException $e) {
                $galatTerakhir = $this->pesanGalat($e);
                $kodeTerakhir = $e->response->status();
                if ($e->response->status() === 400 || $e->response->status() === 403) {
                    break;
                }
            }
        }

        throw new RuntimeException($galatTerakhir ?? 'Gagal menghubungi Gemini.', $kodeTerakhir);
    }

    private function http()
    {
        $http = Http::timeout(config('services.gemini.timeout', 60))->acceptJson();

        $ca = config('services.gemini.ca_bundle');
        if ($ca && is_file($ca)) {
            $http = $http->withOptions(['verify' => $ca]);
        }

        return $http;
    }

    private function rapikan(string $teks): array
    {
        $teks = trim(preg_replace('/^```(json)?|```$/m', '', trim($teks)));
        $data = json_decode($teks, true);

        if (! is_array($data)) {
            throw new RuntimeException('Jawaban Gemini bukan JSON yang valid.');
        }

        $harga = fn ($nilai) => \App\Support\Harga::urai($nilai);

        $pilih = fn ($nilai, array $boleh, $bawaan) => in_array($nilai, $boleh, true) ? $nilai : $bawaan;

        return [
            'status' => $pilih($data['status'] ?? 'ok', ['ok', 'bukan_chart'], 'ok'),
            'tren' => $pilih($data['tren'] ?? null, ['naik', 'turun', 'datar'], 'datar'),
            'support' => [
                'harga' => $harga(data_get($data, 'support.harga')),
                'kekuatan' => $pilih(data_get($data, 'support.kekuatan'), ['kuat', 'lemah'], 'lemah'),
            ],
            'resistance' => [
                'harga' => $harga(data_get($data, 'resistance.harga')),
                'kekuatan' => $pilih(data_get($data, 'resistance.kekuatan'), ['kuat', 'lemah'], 'lemah'),
            ],
            'pola' => array_values(array_filter((array) ($data['pola'] ?? []), 'is_string')),
            'channel' => [
                'ada' => (bool) data_get($data, 'channel.ada', false),
                'arah' => $pilih(data_get($data, 'channel.arah'), ['naik', 'turun'], null),
                'posisi_harga' => is_string(data_get($data, 'channel.posisi_harga')) ? data_get($data, 'channel.posisi_harga') : null,
            ],
            'keyakinan' => $pilih($data['keyakinan'] ?? null, ['tinggi', 'sedang', 'rendah'], 'rendah'),
            'penjelasan' => (string) ($data['penjelasan'] ?? ''),
            'skenario_naik' => (string) ($data['skenario_naik'] ?? ''),
            'skenario_turun' => (string) ($data['skenario_turun'] ?? ''),
            'tidak_terbaca' => (string) ($data['tidak_terbaca'] ?? ''),
        ];
    }

    private function pesanGalat(RequestException $e): string
    {
        $status = $e->response->status();
        $pesan = $e->response->json('error.message') ?? '';

        return match (true) {
            $status === 429 => 'Layanan AI sedang penuh (kuota Gemini habis). Coba lagi beberapa menit lagi.',
            $status === 400 && str_contains($pesan, 'API key') => 'Kunci API Gemini tidak valid. Periksa GEMINI_API_KEY di .env.',
            $status === 403 => 'Kunci API Gemini ditolak (403). Periksa kunci dan pengaturan proyek di Google AI Studio.',
            $status === 404 => 'Model Gemini tidak ditemukan. Ganti GEMINI_MODEL di .env.',
            default => "Gemini menjawab galat {$status}. {$pesan}",
        };
    }
}
