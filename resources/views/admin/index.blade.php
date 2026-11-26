@extends('layouts.app')

@php
    use App\Support\Harga;
@endphp

@section('isi')
<div class="kepala">
    <div>
        <h1>Pengelolaan</h1>
        <p class="pengantar">Pekerjaan yang menunggu keputusan admin. Pilih salah satu angka untuk langsung menuju bagiannya.</p>
    </div>
</div>

<div class="ringkasan">
    @foreach (['pembayaran' => 'Pembayaran menunggu', 'tantangan' => 'Tantangan menunggu', 'review' => 'Review menunggu'] as $kunci => $label)
        <a href="#{{ $kunci }}" class="{{ $jumlah[$kunci] > 0 ? 'perlu' : '' }}"><b>{{ $jumlah[$kunci] }}</b><span>{{ $label }}</span></a>
    @endforeach
</div>

<section id="pembayaran" class="kartu lapang tumpuk">
    <h2>Verifikasi pembayaran</h2>
    <p class="bantu">Pengajuan berlabel Uji coba dibuat saat mode uji coba pembayaran aktif dan tidak melibatkan transfer uang. Tetap periksa kelengkapan data dan bukti sebelum mengaktifkan premium.</p>
    @if ($jumlah['pembayaran'] > $pembayaran->count())<p class="bantu">Menampilkan {{ $pembayaran->count() }} yang paling lama dari {{ $jumlah['pembayaran'] }}. Sisanya muncul setelah ini diproses.</p>@endif
    @if ($pembayaran->isEmpty())
        <p class="redup">Tidak ada pembayaran yang menunggu.</p>
    @else
        <div class="gulir tabel">
            <table>
                <thead><tr><th>Member</th><th>Paket</th><th>Rekening pengirim</th><th>Dikirim</th><th>Bukti</th><th>Tindakan</th></tr></thead>
                <tbody>
                @foreach ($pembayaran as $p)
                    <tr>
                        <td><b>{{ $p->user->name }}</b><br><span class="bantu">{{ $p->user->email }}</span></td>
                        <td>{{ $p->durasi_bulan }} bulan<br><span class="angka bantu">{{ Harga::rupiah($p->nominal()) }}</span>@if ($p->simulasi)<br><span class="tanda info">Uji coba</span>@endif</td>
                        <td>@if ($p->rekening_pengirim){{ \App\Support\Bank::nama($p->bank_pengirim) ?? $p->bank_pengirim }} · {{ $p->rekening_pengirim }}<br><span class="bantu">a.n. {{ $p->nama_pengirim }}</span>@else<span class="redup">tidak dicatat</span>@endif</td>
                        <td class="bantu">{{ $p->created_at->translatedFormat('j M, H.i') }}</td>
                        <td><a href="{{ route('admin.pembayaran.bukti', $p) }}" target="_blank" rel="noopener">Lihat foto</a></td>
                        <td>
                            <form method="POST" action="{{ route('admin.pembayaran.update', $p) }}" class="baris">
                                @csrf
                                @method('PUT')
                                <button class="tombol kecil" type="submit" name="aksi" value="setujui">Setujui</button>
                                <button class="tombol bahaya kecil" type="submit" name="aksi" value="tolak">Tolak</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @if ($pembayaranTerbaru->isNotEmpty())
        <h3>Disetujui baru-baru ini</h3>
        <p class="bantu">Gunakan Cabut premium bila pembayaran salah disetujui atau dananya dikembalikan. Masa berlaku member dimundurkan sebanyak paket itu dan tercatat di catatan admin.</p>
        <div class="gulir tabel">
            <table>
                <thead><tr><th>Member</th><th>Paket</th><th>Disetujui</th><th>Tindakan</th></tr></thead>
                <tbody>
                @foreach ($pembayaranTerbaru as $p)
                    <tr>
                        <td><b>{{ $p->user->name }}</b><br><span class="bantu">{{ $p->user->email }}</span></td>
                        <td>{{ $p->durasi_bulan }} bulan<br><span class="angka bantu">{{ Harga::rupiah($p->nominal()) }}</span>@if ($p->simulasi)<br><span class="tanda info">Uji coba</span>@endif</td>
                        <td class="bantu">{{ $p->diperiksa_pada?->translatedFormat('j M, H.i') }}</td>
                        <td>
                            <form method="POST" action="{{ route('admin.pembayaran.cabut', $p) }}" onsubmit="return confirm('Cabut premium {{ $p->durasi_bulan }} bulan milik {{ addslashes($p->user->name) }}?')">
                                @csrf
                                @method('PUT')
                                <button class="tombol bahaya kecil" type="submit">Cabut premium</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
</section>

<section id="tantangan" class="kartu lapang tumpuk">
    <h2>Persetujuan tantangan</h2>
    @if ($jumlah['tantangan'] > $tantangan->count())<p class="bantu">Menampilkan {{ $tantangan->count() }} yang paling lama dari {{ $jumlah['tantangan'] }}.</p>@endif
    @if ($tantangan->isEmpty())
        <p class="redup">Tidak ada tantangan yang menunggu.</p>
    @else
        <ul class="daftar">
            @foreach ($tantangan as $t)
                <li class="tumpuk">
                    <div class="antara">
                        <b>{{ $t->judul }}</b>
                        <span class="bantu">Oleh {{ $t->mentor->name }} · kunci: {{ \App\Models\Tantangan::ARAH[$t->jawaban_benar] }} · tutup {{ $t->ditutup_pada->translatedFormat('j M, H.i') }}</span>
                    </div>
                    <div class="kisi-2">
                        <figure class="tumpuk rapat"><img src="{{ route('tantangan.gambar', [$t, 'potong']) }}" alt="Chart dipotong untuk {{ $t->judul }}" class="gambar-chart"><figcaption class="bantu">Yang dilihat member</figcaption></figure>
                        <figure class="tumpuk rapat"><img src="{{ route('tantangan.gambar', [$t, 'lanjutan']) }}" alt="Chart lengkap untuk {{ $t->judul }}" class="gambar-chart"><figcaption class="bantu">Kunci jawaban</figcaption></figure>
                    </div>
                    <form method="POST" action="{{ route('admin.tantangan.update', $t) }}" class="baris">
                        @csrf
                        @method('PUT')
                        <button class="tombol kecil" type="submit" name="aksi" value="setujui">Setujui</button>
                        <button class="tombol bahaya kecil" type="submit" name="aksi" value="tolak">Tolak</button>
                    </form>
                </li>
            @endforeach
        </ul>
    @endif
</section>

<section id="review" class="kartu lapang tumpuk">
    <h2>Antrean review mentor</h2>
    <p class="bantu">Penugasan awal mengikuti antrean mentor yang paling sedikit. Admin dapat memindahkan review jika mentor berhalangan. Membuka halaman ini tidak mengubah penugasan.</p>
    @if ($jumlah['review'] > $reviewMenunggu->count())<p class="bantu">Menampilkan {{ $reviewMenunggu->count() }} review terlama dari {{ $jumlah['review'] }}.</p>@endif
    @if ($reviewMenunggu->isEmpty())
        <p class="redup">Tidak ada review yang menunggu.</p>
    @else
        <ul class="daftar">
            @foreach ($reviewMenunggu as $r)
                <li class="antara">
                    <span><b>{{ $r->analisis->judul() }}</b><br><span class="bantu">{{ $r->analisis->user->name }} · {{ $r->mentor?->name ?? 'Mentor tidak tersedia' }} · {{ $r->mulaiDihitung()->diffForHumans() }}</span></span>
                    <form method="POST" action="{{ route('admin.review.pindah', $r) }}">
                        @csrf
                        @method('PUT')
                        <button class="tombol kedua kecil" type="submit">Pindahkan ke mentor lain</button>
                    </form>
                </li>
            @endforeach
        </ul>
    @endif
    <p><a href="{{ route('admin.pengguna', ['peran' => 'mentor']) }}">Kelola akun mentor</a></p>
</section>

<section id="catatan" class="kartu lapang tumpuk">
    <h2>Catatan tindakan admin</h2>
    <p class="bantu">20 tindakan terakhir: siapa melakukan apa dan kapan.</p>
    @if ($catatan->isEmpty())
        <p class="redup">Belum ada tindakan.</p>
    @else
        <div class="gulir tabel">
            <table>
                <thead><tr><th>Tindakan</th><th>Keterangan</th><th>Admin</th><th>Waktu</th></tr></thead>
                <tbody>
                @foreach ($catatan as $c)
                    <tr>
                        <td><b>{{ $c->tindakan }}</b></td>
                        <td class="kecil">{{ $c->keterangan }}</td>
                        <td class="kecil">{{ $c->admin?->name ?? 'Admin dihapus' }}</td>
                        <td class="bantu">{{ $c->created_at->translatedFormat('j M, H.i') }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
</section>
@endsection
