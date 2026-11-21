@extends('layouts.app')
@php
    use App\Models\Analisis;
    use App\Models\Review;
    $tren = $analisis->h('tren');
    $kelasTren = ['naik' => 'naik', 'turun' => 'turun'][$tren] ?? '';
    $langkah = [
        'Diunggah' => true,
        'Dijelaskan AI' => $analisis->status === 'selesai',
        'Direview mentor' => $review && $review->status === 'selesai',
    ];
@endphp
@section('isi')
    <div class="kepala">
        <div>
            <a href="{{ route('analisis.index') }}" class="kembali">@include('partials.ikon', ['nama' => 'kembali']) Semua riwayat</a>
            <h1>{{ $analisis->judul() }}</h1>
            <ol class="alur" aria-label="Status analisis">
                @foreach ($langkah as $label => $selesai)
                    <li class="{{ $selesai ? 'selesai' : '' }}"><span class="titik"></span>{{ $label }}</li>
                @endforeach
            </ol>
        </div>
        <div class="baris">
            <span class="tanda {{ $analisis->status === 'gagal' ? 'turun' : 'info' }}">{{ $analisis->status === 'gagal' ? 'Gagal dibaca' : $analisis->labelStatusReview() }}</span>
            @if ($analisis->kode_saham && $analisis->status === 'selesai')
                <a class="tombol kedua kecil" href="{{ route('analisis.saham', ['kode' => $analisis->kode_saham, 'rentang' => $analisis->rentang]) }}">@include('partials.ikon', ['nama' => 'chart']) Lihat perubahan {{ $analisis->kode_saham }} dari waktu ke waktu</a>
            @endif
        </div>
    </div>
    <div class="hasil-grid">
        <div class="hasil-kolom">
            <section class="kartu tumpuk rapat" aria-label="Chart yang diunggah">
                <div class="layar-chart">
                    <div class="ket"><span>{{ $analisis->judul() }}</span><span>{{ $analisis->created_at->translatedFormat('j M Y, H.i') }}</span></div>
                    <img src="{{ $analisis->urlGambar() }}" alt="Screenshot chart {{ $analisis->judul() }}">
                </div>
                <span class="bantu">Diunggah {{ $analisis->created_at->translatedFormat('j F Y, H.i') }}</span>
            </section>
            @php
                $tebak = $analisis->bandingkanTebakan();
            @endphp
            @if ($tebak)
                @php
                    $namaTren = ['naik' => 'Naik', 'turun' => 'Turun', 'datar' => 'Datar'];
                    $angkaHarga = fn ($v) => number_format((float) $v, floor((float) $v) == (float) $v ? 0 : 2, ',', '.');
                    $persen = fn ($v) => number_format($v, 1, ',', '.') . '%';
                @endphp
                <section class="kartu tumpuk" aria-labelledby="judul-tebakan">
                    <h2 id="judul-tebakan">Bacaan Anda vs AI</h2>
                    <div class="gulir tabel">
                        <table>
                            <thead><tr><th>Yang dibaca</th><th class="angka">Tebakan Anda</th><th class="angka">Hasil AI</th><th>Hasil</th></tr></thead>
                            <tbody>
                                <tr>
                                    <td><b>Tren</b></td>
                                    <td class="angka">{{ $namaTren[$tebak['tren']['tebakan']] ?? '—' }}</td>
                                    <td class="angka">{{ $namaTren[$tebak['tren']['ai']] ?? '—' }}</td>
                                    <td><span class="tanda {{ $tebak['tren']['sama'] ? 'naik' : 'tunggu' }}">{{ $tebak['tren']['sama'] ? 'Sama' : 'Berbeda' }}</span></td>
                                </tr>
                                @foreach (['support' => 'Support', 'resistance' => 'Resistance'] as $kunci => $label)
                                    @php
                                        $b = $tebak[$kunci];
                                    @endphp
                                    <tr>
                                        <td><b>{{ $label }}</b></td>
                                        <td class="angka">{{ $b['tebakan'] !== null ? $angkaHarga($b['tebakan']) : 'Tidak diisi' }}</td>
                                        <td class="angka">{{ $b['ai'] !== null ? $angkaHarga($b['ai']) : 'Tidak terbaca' }}</td>
                                        <td>
                                            @if ($b['status'] === 'dekat')<span class="tanda naik">Dekat · selisih {{ $persen($b['selisih']) }}</span>
                                            @elseif ($b['status'] === 'jauh')<span class="tanda tunggu">Jauh · selisih {{ $persen($b['selisih']) }}</span>
                                            @else<span class="redup">—</span>@endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <p class="bantu">Selisih sampai {{ Analisis::TOLERANSI_TEBAKAN }}% dianggap dekat, sama dengan toleransi AI saat menentukan support dan resistance. Berbeda dengan AI tidak otomatis berarti Anda salah: AI bisa keliru, dan mentor yang menilai.</p>
                </section>
            @endif
            <section class="kartu lapang tumpuk" id="catatan">
                <div class="antara">
                    <h2>Catatan belajar saya</h2>
                    <span class="bantu">Hanya Anda yang bisa melihat catatan ini</span>
                </div>
                <form method="POST" action="{{ route('analisis.update', $analisis) }}" class="tumpuk">
                    @csrf
                    @method('PUT')
                    <div class="isian">
                        <label for="catatanBelajar">Apa yang Anda pelajari dari chart ini?</label>
                        <textarea id="catatanBelajar" name="catatan" rows="4" maxlength="2000" aria-describedby="bantu-catatan">{{ old('catatan', $analisis->catatan) }}</textarea>
                        <span class="bantu" id="bantu-catatan">Misalnya bacaan Anda sendiri sebelum melihat hasil AI, atau koreksi mentor yang ingin Anda ingat.</span>
                        @error('catatan') <span class="salah">{{ $message }}</span> @enderror
                    </div>
                    <div class="kisi-2">
                        <div class="isian">
                            <label for="kodeEdit">Kode saham</label>
                            <input id="kodeEdit" name="kode_saham" maxlength="4" value="{{ old('kode_saham', $analisis->kode_saham) }}">
                            @error('kode_saham') <span class="salah">{{ $message }}</span> @enderror
                        </div>
                        <div class="isian">
                            <label for="rentangEdit">Rentang waktu chart</label>
                            <select id="rentangEdit" name="rentang">
                                @foreach (Analisis::RENTANG as $kode => $label)
                                    <option value="{{ $kode }}" @selected(old('rentang', $analisis->rentang) === $kode)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <p><button class="tombol kedua" type="submit">Simpan catatan</button></p>
                </form>
            </section>
        </div>
        <div class="hasil-kolom">
            <section class="kartu lapang tumpuk" aria-live="polite">
                @if ($analisis->status === 'gagal')
                    <h2>AI belum berhasil membaca chart</h2>
                    <p class="pesan galat">{{ $analisis->pesan_galat }}</p>
                    <p class="bantu">Analisis yang gagal tidak memakai kuota.</p>
                    <form method="POST" action="{{ route('analisis.ulang', $analisis) }}">
                        @csrf
                        <button class="tombol" type="submit">Coba baca ulang</button>
                    </form>
                @elseif ($analisis->status === 'diproses')
                    <h2>AI sedang membaca chart</h2>
                    @if ($analisis->macet())
                        <p>Proses ini terlalu lama dan sepertinya terhenti. Anda bisa mencoba membaca ulang.</p>
                        <form method="POST" action="{{ route('analisis.ulang', $analisis) }}">
                            @csrf
                            <button class="tombol" type="submit">Coba baca ulang</button>
                        </form>
                    @else
                        <p>Biasanya selesai dalam beberapa detik. Halaman ini diperbarui otomatis.</p>
                        <div class="proses" role="progressbar" aria-label="AI sedang membaca chart"><i></i></div>
                        @push('skrip')
                            <script>setTimeout(function () { window.location.reload(); }, 3000);</script>
                        @endpush
                    @endif
                @elseif ($analisis->h('status') === 'bukan_chart')
                    <h2>Gambar ini sepertinya bukan chart harga</h2>
                    <p>{{ $analisis->h('penjelasan') ?: 'Coba unggah screenshot chart candlestick yang memperlihatkan sumbu harga.' }}</p>
                    <p><a class="tombol" href="{{ route('analisis.create') }}">Unggah chart lain</a></p>
                @else
                    <div class="antara">
                        <h2>Penjelasan AI</h2>
                        <span class="bantu">{{ $analisis->created_at->diffForHumans() }}</span>
                    </div>
                    <dl class="angka-kunci">
                        <div class="{{ $kelasTren }}"><dt>Tren</dt><dd>{{ ucfirst($tren) }}</dd></div>
                        <div><dt>Support ({{ $analisis->h('support.kekuatan') }})</dt><dd>{{ Analisis::formatHarga($analisis->h('support.harga')) }}</dd></div>
                        <div><dt>Resistance ({{ $analisis->h('resistance.kekuatan') }})</dt><dd>{{ Analisis::formatHarga($analisis->h('resistance.harga')) }}</dd></div>
                    </dl>
                    <p class="kecil redup">
                        Pola: <b>{{ implode(', ', $analisis->h('pola', [])) ?: 'tidak ada yang jelas' }}</b>
                        · Keyakinan AI: <b>{{ $analisis->h('keyakinan') }}</b>
                        @if ($analisis->h('channel.ada'))
                            · Channel {{ $analisis->h('channel.arah') }}{{ $analisis->h('channel.posisi_harga') ? ', harga ' . $analisis->h('channel.posisi_harga') : '' }}
                        @endif
                    </p>
                    <p class="isi-teks">{{ $analisis->h('penjelasan') }}</p>
                    @if ($analisis->h('skenario_naik') || $analisis->h('skenario_turun'))
                        <div class="kisi-2">
                            <div class="tumpuk rapat pemisah"><b class="kecil">Jika harga naik</b><span class="kecil">{{ $analisis->h('skenario_naik') }}</span></div>
                            <div class="tumpuk rapat pemisah"><b class="kecil">Jika harga turun</b><span class="kecil">{{ $analisis->h('skenario_turun') }}</span></div>
                        </div>
                    @endif
                    @if ($analisis->h('tidak_terbaca'))
                        <div class="pesan catatan"><span><b>Yang tidak bisa dibaca dari gambar ini.</b>
                                {{ $analisis->h('tidak_terbaca') }}</span></div>
                    @endif
                    @if (! $review)
                        <div class="baris">
                            @if ($premium)
                                <form method="POST" action="{{ route('review.store', $analisis) }}">
                                    @csrf
                                    <button class="tombol" type="submit">Minta review mentor</button>
                                </form>
                            @else
                                <a class="tombol" href="{{ route('paket.index') }}">Minta review mentor (khusus premium)</a>
                            @endif
                            <a class="tombol kedua" href="{{ route('analisis.create') }}">Unggah chart lain</a>
                        </div>
                    @endif
                    <p class="bantu">Penjelasan ini untuk belajar membaca chart, bukan saran membeli atau menjual.</p>
                @endif
            </section>
            @if ($review)
                <section class="kartu lapang tumpuk">
                    @if ($review->status === 'menunggu')
                        <h2>Menunggu review mentor</h2>
                        <p>Analisis ini ada di antrean {{ $review->mentor?->name ?? 'mentor' }}. @if ($review->terlambat()) Sudah melewati batas {{ Review::batasMenit() }} menit. Admin dapat memindahkannya jika mentor berhalangan. Bila tidak ada yang menanggapi, Anda boleh membatalkannya dan memintanya lagi. @else Batas review {{ Review::batasMenit() }} menit, sisa sekitar {{ $review->sisaMenit() }} menit. Jika terlewat, admin dapat menindaklanjuti penugasannya. @endif </p>
                        @if ($review->terlambat())
                            <form method="POST" action="{{ route('review.batal', $review) }}" onsubmit="return confirm('Batalkan permintaan review ini? Anda bisa memintanya lagi nanti.')">
                                @csrf
                                <button class="tombol kedua" type="submit">Batalkan permintaan review</button>
                            </form>
                        @endif
                    @else
                        @php
                            $kelasNilai = ['benar' => 'naik', 'koreksi' => 'tunggu', 'salah' => 'turun']
                            [$review->penilaian];
                        @endphp
                        <div class="antara">
                            <h2>Review mentor</h2>
                            <span class="tanda {{ $kelasNilai }}">{{ Review::PENILAIAN[$review->penilaian] }} </span>
                        </div>
                        <p class="bantu">{{ $review->mentor?->name }}, mentor · dikirim {{ $review->direview_pada->translatedFormat('j M, H.i') }}</p>
                        <p class="isi-teks">{{ $review->catatan ?: 'Bacaan AI sudah tepat, tidak ada koreksi.' }}</p>
                        @if ($analisis->status_review === 'direview')
                            <form method="POST" action="{{ route('review.setuju', $review) }}">
                                @csrf
                                <button class="tombol" type="submit">Setuju, tandai selesai</button>
                            </form>
                        @elseif ($analisis->status_review === 'final')
                            <p class="pesan sukses">Analisis ini sudah final.</p>
                        @endif
                    @endif
                </section>
            @endif
        </div>
    </div>
    <section class="antara">
        <p class="bantu">
            @if ($analisis->bolehDihapus())
                Menghapus analisis ikut menghapus screenshot. Berguna jika gambar memperlihatkan saldo
                atau nama akun Anda. Kuota yang sudah terpakai tidak kembali.
            @else
                Analisis ini sedang diproses atau diperiksa mentor, jadi belum bisa dihapus.
            @endif
        </p>
        @if ($analisis->bolehDihapus())
            <form method="POST" action="{{ route('analisis.destroy', $analisis) }}" onsubmit="return confirm('Hapus analisis dan screenshot ini? Tindakan ini tidak bisa dibatalkan.')">
                @csrf
                @method('DELETE')
                <button class="tombol bahaya kecil" type="submit">Hapus analisis</button>
            </form>
        @endif
    </section>
@endsection