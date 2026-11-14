@php
    $bentuk = [
        'tambah' => '<path d="M12 5v14M5 12h14"/>',
        'riwayat' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'bendera' => '<path d="M5 21V4M5 4h12l-2.5 4L17 12H5"/>',
        'paket' => '<path d="M3 8l9-5 9 5-9 5-9-5z"/><path d="M3 8v8l9 5 9-5V8"/>',
        'antrean' => '<path d="M4 13h4l2 3h4l2-3h4"/><path d="M4 13l2.5-7h11L20 13v5H4z"/>',
        'kelola' => '<rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/>',
        'pengguna' => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c.8-3.5 3.4-5.5 6.5-5.5s5.7 2 6.5 5.5"/><path d="M16 4.5a3.5 3.5 0 0 1 0 7M18 14.5c2 .7 3.2 2.6 3.5 5.5"/>',
        'profil' => '<circle cx="12" cy="8" r="4"/><path d="M4 21c1-4 4-6 8-6s7 2 8 6"/>',
        'perisai' => '<path d="M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6z"/>',
        'bantuan' => '<circle cx="12" cy="12" r="9"/><path d="M9.5 9.5a2.5 2.5 0 1 1 3.5 2.3c-.7.4-1 .9-1 1.7M12 17h.01"/>',
        'keluar' => '<path d="M15 4h3a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-3M10 8l-4 4 4 4M6 12h10"/>',
        'unggah' => '<path d="M12 16V4M7 9l5-5 5 5"/><path d="M4 16v3a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-3"/>',
        'cek' => '<path d="M5 12.5l4.5 4.5L19 7.5"/>',
        'silang' => '<path d="M7 7l10 10M17 7L7 17"/>',
        'kembali' => '<path d="M15 6l-6 6 6 6"/>',
        'chart' => '<path d="M4 20V4M4 20h16"/><path d="M8 15l4-5 3 3 5-6"/>',
        'naik' => '<path d="M5 15l7-7 7 7"/>',
        'datar' => '<path d="M5 12h14"/>',
        'turun' => '<path d="M5 9l7 7 7-7"/>',
    ];
@endphp
<svg class="ikon" viewBox="0 0 24 24" aria-hidden="true">{!! $bentuk[$nama] !!}</svg>