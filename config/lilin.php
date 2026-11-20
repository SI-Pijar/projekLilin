<?php

return [
    'pembayaran_simulasi' => (bool) env('LILIN_PEMBAYARAN_SIMULASI', false),
    'batas_review_menit' => (int) env('LILIN_BATAS_REVIEW_MENIT', 15),

    'kuota_premium_bulanan' => (int) env('LILIN_KUOTA_PREMIUM_BULANAN', 100),

    'harga_per_bulan' => (int) env('LILIN_HARGA_BULAN', 333000),

    'rekening' => [
        'bank' => env('LILIN_REKENING_BANK'),
        'nomor' => env('LILIN_REKENING_NOMOR'),
        'nama' => env('LILIN_REKENING_NAMA'),
    ],

    'email_kontak' => env('LILIN_EMAIL_KONTAK'),
];
