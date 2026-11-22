<?php

namespace App\Services;

use App\Models\Analisis;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class PembacaChart
{
    public function __construct(private GeminiChart $gemini)
    {
    }

    public function baca(Analisis $analisis): void
    {
        if ($analisis->status !== 'diproses') {
            return;
        }

        $disk = Storage::disk('public');

        try {
            $jawaban = $this->gemini->baca(
                $disk->path($analisis->gambar),
                $disk->mimeType($analisis->gambar) ?: 'image/png',
                $analisis->kode_saham,
                Analisis::RENTANG[$analisis->rentang] ?? $analisis->rentang,
            );

            $analisis->update([
                'status' => 'selesai',
                'hasil' => $jawaban['hasil'],
                'model_ai' => $jawaban['model'],
                'pesan_galat' => null,
            ]);
        } catch (ConnectionException $e) {
            Log::warning('Gemini tidak terjangkau', ['analisis' => $analisis->id, 'galat' => $e->getMessage()]);

            $ssl = str_contains($e->getMessage(), 'SSL') || str_contains($e->getMessage(), 'certificate');
            $pesan = app()->isLocal() && $ssl
                ? 'Sertifikat SSL tidak ditemukan. Isi GEMINI_CA_BUNDLE di .env dengan C:\laragon\etc\ssl\cacert.pem.'
                : 'Layanan AI sedang tidak bisa dihubungi. Coba lagi beberapa saat lagi.';

            $analisis->update(['status' => 'gagal', 'pesan_galat' => $pesan]);
        } catch (Throwable $e) {
            Log::warning('Analisis gagal dibaca', ['analisis' => $analisis->id, 'galat' => $e->getMessage()]);

            $pesan = match (true) {
                $e->getCode() === 429 => 'Layanan AI sedang penuh. Coba lagi beberapa menit lagi; analisis yang gagal tidak memakai kuota harian Anda.',
                app()->isLocal() => $e->getMessage(),
                default => 'AI belum bisa membaca gambar ini. Coba lagi, atau unggah screenshot chart yang lebih jelas.',
            };

            $analisis->update(['status' => 'gagal', 'pesan_galat' => $pesan]);
        }
    }

}
