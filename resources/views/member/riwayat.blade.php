@extends('layouts.app')

@section('isi')
<div class="kepala">
    <div>
        <h1>Riwayat dan review</h1>
        <p class="pengantar">{{ $premium ? 'Semua analisis Anda.' : 'Analisis 7 hari terakhir. Premium menyimpan riwayat tanpa batas waktu.' }}</p>
    </div>
    <a class="tombol" href="{{ route('analisis.create') }}">Analisis baru</a>
</div>

@if ($kodeSaham->isNotEmpty())
    <section class="kartu tumpuk rapat" aria-labelledby="judul-saham">
        <h2 id="judul-saham">Lihat perubahan per saham</h2>
        <p class="bantu">Pilih satu kode untuk melihat bagaimana support, resistance, dan tren hasil analisis Anda bergeser dari waktu ke waktu.</p>
        <nav class="saring" aria-label="Kode saham">
            @foreach ($kodeSaham as $k)
                <a href="{{ route('analisis.saham', $k->kode_saham) }}">{{ $k->kode_saham }} · {{ $k->jumlah }} analisis</a>
            @endforeach
        </nav>
    </section>
@endif

<section class="tumpuk">
    @if ($daftar->isEmpty())
        <div class="kartu kosong">@include('partials.ikon', ['nama' => 'chart'])<span>Belum ada analisis dalam rentang ini.</span><a class="tombol" href="{{ route('analisis.create') }}">Unggah chart pertama</a></div>
    @else
        <div class="gulir tabel">
            <table>
                <thead><tr><th>Chart</th><th>Tren</th><th class="angka">Support</th><th class="angka">Resistance</th><th>Review</th><th>Waktu</th><th></th></tr></thead>
                <tbody>
                @foreach ($daftar as $item)
                    <tr>
                        <td><b>{{ $item->judul() }}</b></td>
                        <td>
                            @if ($item->status === 'selesai' && $item->h('status') !== 'bukan_chart')
                                <span class="tanda {{ ['naik' => 'naik', 'turun' => 'turun'][$item->h('tren')] ?? '' }}">{{ ucfirst($item->h('tren')) }}</span>
                            @elseif ($item->status === 'gagal')
                                <span class="tanda turun">Gagal</span>
                            @else
                                <span class="redup">—</span>
                            @endif
                        </td>
                        <td class="angka">{{ $item->status === 'selesai' ? \App\Models\Analisis::formatHarga($item->h('support.harga')) : '—' }}</td>
                        <td class="angka">{{ $item->status === 'selesai' ? \App\Models\Analisis::formatHarga($item->h('resistance.harga')) : '—' }}</td>
                        <td><span class="tanda {{ ['direview' => 'tunggu', 'final' => 'naik', 'menunggu' => 'info'][$item->status_review] ?? '' }}">{{ $item->labelStatusReview() }}</span></td>
                        <td class="bantu">{{ $item->created_at->translatedFormat('j M, H.i') }}</td>
                        <td><a href="{{ route('analisis.show', $item) }}">Buka</a></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        {{ $daftar->links('partials.paginasi') }}
    @endif
</section>
@endsection
