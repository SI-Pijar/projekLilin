<?php

namespace App\Support;

class Harga
{
    public static function urai(mixed $nilai): ?float
    {
        if (is_int($nilai) || is_float($nilai)) {
            return (float) $nilai;
        }
        if (! is_string($nilai)) {
            return null;
        }

        $nilai = preg_replace('/[^0-9.,]/', '', $nilai);
        if (preg_match('/^\d{1,3}(\.\d{3})+(,\d+)?$/', $nilai) || str_contains($nilai, ',')) {
            $nilai = str_replace(['.', ','], ['', '.'], $nilai);
        }

        return is_numeric($nilai) ? (float) $nilai : null;
    }

    public static function rupiah(int|float $angka): string
    {
        return 'Rp ' . number_format($angka, 0, ',', '.');
    }

    public static function paket(int $bulan): int
    {
        return $bulan * (int) config('lilin.harga_per_bulan');
    }

    public static function rekeningSiap(): bool
    {
        $r = config('lilin.rekening');

        return filled($r['bank'] ?? null) && filled($r['nomor'] ?? null) && filled($r['nama'] ?? null);
    }
}
