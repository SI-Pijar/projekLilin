<?php

namespace App\Support;

class Bank
{
    public const DAFTAR = [
        'bca' => ['BCA', 10, 10],
        'mandiri' => ['Bank Mandiri', 10, 13],
        'bni' => ['BNI', 9, 10],
        'bri' => ['BRI', 10, 15],
        'bsi' => ['BSI (Bank Syariah Indonesia)', 10, 10],
        'cimb' => ['CIMB Niaga', 12, 13],
        'permata' => ['Permata Bank', 10, 10],
        'danamon' => ['Bank Danamon', 9, 10],
        'btn' => ['BTN', 16, 16],
        'jago' => ['Bank Jago', 12, 12],
        'seabank' => ['SeaBank', 12, 12],
        'jenius' => ['Jenius (BTPN)', 12, 12],
    ];

    public static function nama(?string $kode): ?string
    {
        return self::DAFTAR[$kode][0] ?? null;
    }

    public static function bersihkan(?string $nomor): string
    {
        return preg_replace('/\D+/', '', (string) $nomor);
    }

    public static function petunjuk(string $kode): string
    {
        [, $min, $max] = self::DAFTAR[$kode];

        return $min === $max ? "{$min} digit" : "{$min} sampai {$max} digit";
    }

    public static function nomorSesuai(string $kode, string $nomor): bool
    {
        if (! isset(self::DAFTAR[$kode])) {
            return false;
        }
        [, $min, $max] = self::DAFTAR[$kode];

        return strlen($nomor) >= $min && strlen($nomor) <= $max;
    }
}
