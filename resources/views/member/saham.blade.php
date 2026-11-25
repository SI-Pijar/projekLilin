@extends('layouts.app')
@php
    use App\Models\Analisis;
    $namaTren = ['naik' => 'Naik', 'turun' => 'Turun', 'datar' => 'Datar'];
    $kelasTren = ['naik' => 'naik', 'turun' => 'turun'];
    $persen = fn ($p) => ($p > 0 ? '+' : ($p < 0 ? '−' : '')) . number_format(abs($p), 1, ',', '.') . '%';
    $kelasPersen = fn ($p) => $p > 0 ? 'naik' : ($p < 0 ? 'turun' : '');
    $angka = fn ($v) => number_format((float) $v, floor((float) $v) == (float) $v ? 0 : 2, ',', '.');
    $titik = $seri->map(fn ($a) => [
        's' => is_numeric($a->h('support.harga')) ? (float) $a->h('support.harga') : null,
        'r' => is_numeric($a->h('resistance.harga')) ? (float) $a->h('resistance.harga') : null,
    ]);
    $semuaNilai = $titik->flatMap(fn ($t) => [$t['s'], $t['r']])->filter()->values();
    $bisaGrafik = $seri->count() >= 2 && $semuaNilai->count() >= 2;
    if ($bisaGrafik) {
        [$L, $T, $kiri, $kanan, $atas, $bawah] = [720, 230, 64, 100, 16, 30];
        $min = $semuaNilai->min();
        $maks = $semuaNilai->max();
        $jarak = max($maks - $min, $maks * 0.02);
        $min -= $jarak * 0.1;
        $maks += $jarak * 0.1;
        $x = fn ($i) => $kiri + $i * (($L - $kiri - $kanan) / max($seri->count() - 1, 1));
        $y = fn ($v) => $atas + ($maks - $v) / ($maks - $min) * ($T - $atas - $bawah);
        $garis = function ($kunci) use ($titik, $x, $y) {
            $p = [];
            foreach ($titik as $i => $t) {
                if ($t[$kunci] !== null) {
                    $p[] = round($x($i), 1) . ',' . round($y($t[$kunci]), 1);
                }
            }
            return implode(' ', $p);
        };
    }
@endphp
@section('isi')
    <div class="kepala">
        <div>
            <a href="{{ route('analisis.index') }}" class="kembali">@include('partials.ikon', ['nama' => 'kembali']) Semua riwayat</a>
            <h1>{{ $kode }} dari waktu ke waktu</h1>
            <p class="pengantar">Bagaimana hasil analisis Anda untuk {{ $kode }} bergeser. Bandingkan hanya pada rentang chart yang sama.</p>
        </div>
    </div>
    @if ($perRentang->count() > 1)
        <nav class="saring" aria-label="Rentang chart">
            @foreach ($perRentang as $kodeRentang => $jumlah)
                <a href="{{ route('analisis.saham', ['kode' => $kode, 'rentang' => $kodeRentang]) }}" @if ($kodeRentang === $rentang) aria-current="true" @endif>{{ Analisis::RENTANG[$kodeRentang] ?? $kodeRentang }} · {{ $jumlah }}</a>
            @endforeach
        </nav>
    @endif
    <dl class="angka-kunci">
        <div><dt>Analisis ({{ strtolower(Analisis::RENTANG[$rentang] ?? $rentang) }})</dt><dd>{{ $seri->count() }}</dd></div>
        <div>
            <dt>Support</dt>
            @if ($support['cukup'])
                <dd>{{ $angka($support['awal']) }} → {{ $angka($support['kini']) }}</dd>
                <dd><span class="tanda {{ $kelasPersen($support['persen']) }}">{{ $persen($support['persen']) }}</span></dd>
            @else
                <dd>—</dd>
            @endif
        </div>
        <div>
            <dt>Resistance</dt>
            @if ($resistance['cukup'])
                <dd>{{ $angka($resistance['awal']) }} → {{ $angka($resistance['kini']) }}</dd>
                <dd><span class="tanda {{ $kelasPersen($resistance['persen']) }}">{{ $persen($resistance['persen']) }}</span></dd>
            @else
                <dd>—</dd>
            @endif
        </div>
        <div>
            <dt>Tren</dt>
            @if ($tren['cukup'])
                <dd>{{ $namaTren[$tren['awal']] ?? '—' }} → {{ $namaTren[$tren['kini']] ?? '—' }}</dd>
                <dd class="bantu">berubah {{ $tren['berubah'] }} kali</dd>
            @else
                <dd>—</dd>
            @endif
        </div>
    </dl>
    @if ($seri->count() < 2)
        <p class="pesan catatan">Baru ada satu analisis {{ $kode }} pada rentang ini. Unggah chart {{ $kode }} lagi nanti untuk melihat perubahannya.</p>
    @elseif ($bisaGrafik)
        <section class="kartu lapang tumpuk" aria-labelledby="judul-grafik">
            <h2 id="judul-grafik">Support dan resistance tiap analisis</h2>
            <svg viewBox="0 0 {{ $L }} {{ $T }}" role="img" aria-label="Grafik garis support dan resistance {{ $kode }} dari {{ $seri->count() }} analisis" width="100%">
                @foreach ([0, 0.5, 1] as $bagian)
                    @php
                        $nilaiGaris = $maks - ($maks - $min) * $bagian;
                    @endphp
                    <line x1="{{ $kiri }}" x2="{{ $L - $kanan }}" y1="{{ round($y($nilaiGaris), 1) }}" y2="{{ round($y($nilaiGaris), 1) }}" stroke="#e7e2d5"/>
                    <text x="{{ $kiri - 8 }}" y="{{ round($y($nilaiGaris), 1) + 4 }}" text-anchor="end" font-size="12" font-family="Consolas, monospace" fill="#636a64">{{ $angka($nilaiGaris) }}</text>
                @endforeach
                <polyline points="{{ $garis('r') }}" fill="none" stroke="#9c3d28" stroke-width="2" stroke-dasharray="6 4"/>
                <polyline points="{{ $garis('s') }}" fill="none" stroke="#2b6449" stroke-width="2"/>
                @foreach ($titik as $i => $t)
                    @if ($t['r'] !== null)<rect x="{{ round($x($i), 1) - 3.5 }}"
                        y="{{ round($y($t['r']), 1) - 3.5 }}" width="7" height="7" fill="#9c3d28"/>@endif
                    @if ($t['s'] !== null)<rect x="{{ round($x($i), 1) - 3.5 }}"
                        y="{{ round($y($t['s']), 1) - 3.5 }}" width="7" height="7" fill="#2b6449"/>@endif
                @endforeach
                @php
                    $akhirR = $titik->map(fn ($t) => $t['r'])->filter()->last();
                    $akhirS = $titik->map(fn ($t) => $t['s'])->filter()->last();
                @endphp
                @if ($akhirR !== null)<text x="{{ $L - $kanan + 10 }}" y="{{ round($y($akhirR), 1) + 4 }}" font-size="13" font-weight="600" fill="#9c3d28">Resistance</text>@endif
                @if ($akhirS !== null)<text x="{{ $L - $kanan + 10 }}" y="{{ round($y($akhirS), 1) + 4 }}" font-size="13" font-weight="600" fill="#2b6449">Support</text>@endif
                <text x="{{ $kiri }}" y="{{ $T - 8 }}" font-size="12" fill="#636a64">{{ $seri->first()->created_at->translatedFormat('j M') }}</text>
                <text x="{{ $L - $kanan }}" y="{{ $T - 8 }}" text-anchor="end" font-size="12" fill="#636a64">{{ $seri->last()->created_at->translatedFormat('j M') }}</text>
            </svg>
        </section>
    @endif
    <section class="tumpuk">
        <h2>Semua analisis {{ $kode }}</h2>
        <div class="gulir tabel">
            <table>
                <thead><tr><th>Tanggal</th><th>Tren</th><th class="angka">Support</th><th class="angka">Resistance</th><th>Pola</th><th>Review</th><th></th></tr></thead>
                <tbody>
                    @foreach ($seri->reverse() as $a)
                        <tr>
                            <td class="bantu">{{ $a->created_at->translatedFormat('j M Y, H.i') }}</td>
                            <td><span class="tanda {{ $kelasTren[$a->h('tren')] ?? '' }}">{{ $namaTren[$a->h('tren')] ?? '—' }}</span></td>
                            <td class="angka">{{ Analisis::formatHarga($a->h('support.harga')) }}</td>
                            <td class="angka">{{ Analisis::formatHarga($a->h('resistance.harga')) }}</td>
                            <td class="kecil">{{ implode(', ', (array) $a->h('pola', [])) ?: '—' }}</td>
                            <td><span class="tanda">{{ $a->labelStatusReview() }}</span></td>
                            <td><a href="{{ route('analisis.show', $a) }}">Buka</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
    <p class="bantu">Angka diambil AI dari gambar chart, jadi bisa meleset sedikit. Perubahan di sini adalah catatan belajar, bukan sinyal beli atau jual.@unless ($premium) Member biasa hanya melihat 7 hari terakhir; premium menyimpan riwayat lengkap.@endunless</p>
@endsection