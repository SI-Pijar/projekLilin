@extends('layouts.app')
@section('isi')
    <div class="kepala">
        <div>
            <h1>Analisis baru</h1>
            <p class="pengantar">Unggah screenshot chart, tulis tebakan Anda, lalu AI menjelaskan apa yang terlihat.</p>
        </div>
    </div>
    <div class="dua">
        <form method="POST" action="{{ route('analisis.store') }}" enctype="multipart/form-data" class="kartu lapang tumpuk longgar" id="formAnalisis">
            @csrf
            <div class="langkah-form">
                <span class="nomor">1</span>
                <div>
                    <h2>Unggah screenshot chart</h2>
                    <label class="unggah" id="areaUnggah">
                        <input id="gambar" name="gambar" type="file" accept="image/png,image/jpeg,image/webp" required aria-describedby="bantu-gambar">
                        <span id="petunjukUnggah" class="tumpuk rapat">
                            @include('partials.ikon', ['nama' => 'unggah'])
                            <b>Tarik gambar ke sini atau klik untuk memilih</b>
                            <span class="bantu" id="bantu-gambar">PNG, JPG, atau WEBP, paling besar 5 MB. Pastikan angka harga di sumbu kanan terlihat.</span>
                        </span>
                        <img id="gambarPratinjau" alt="Pratinjau screenshot yang akan dianalisis" hidden>
                        <span class="bantu" id="namaFile"></span>
                    </label>
                    @error('gambar') <span class="salah">{{ $message }}</span> @enderror
                    <div class="kisi-2">
                        <div class="isian">
                            <label for="kode_saham">Kode saham <span class="redup">(boleh kosong)</span></label>
                            <input id="kode_saham" name="kode_saham" value="{{ old('kode_saham') }}" maxlength="4" placeholder="Contoh: BBRI" autocomplete="off">
                            @error('kode_saham') <span class="salah">{{ $message }}</span> @enderror
                        </div>
                        <div class="isian">
                            <label for="rentang">Rentang waktu chart</label>
                            <select id="rentang" name="rentang">
                                @foreach (\App\Models\Analisis::RENTANG as $kode => $label)
                                    <option value="{{ $kode }}" @selected(old('rentang', '1D') === $kode)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class="langkah-form pemisah">
                <span class="nomor">2</span>
                <div>
                    <div class="tumpuk rapat">
                        <h2>Tebak dulu sebelum melihat jawaban AI</h2>
                        <p class="bantu">Pandang chartnya, lalu tulis bacaan Anda sendiri. Setelah AI selesai, keduanya ditampilkan berdampingan.</p>
                    </div>
                    <fieldset>
                        <legend>Arah tren menurut Anda</legend>
                        <div class="pilihan">
                            @foreach (\App\Models\Tantangan::ARAH as $nilai => $label)
                                <label><input type="radio" name="tebak_tren" value="{{ $nilai }}" @checked(old('tebak_tren') === $nilai) required><span class="{{ $nilai }}">@include('partials.ikon', ['nama' => $nilai])</span>Tren {{ strtolower($label) }}</label>
                            @endforeach
                        </div>
                        @error('tebak_tren') <span class="salah">{{ $message }}</span> @enderror
                    </fieldset>
                    <div class="kisi-2">
                        <div class="isian">
                            <label for="tebak_support">Support <span class="redup">(boleh kosong)</span></label>
                            <input id="tebak_support" name="tebak_support" value="{{ old('tebak_support') }}" inputmode="decimal" placeholder="Contoh: 4790">
                            @error('tebak_support') <span class="salah">{{ $message }}</span> @enderror
                        </div>
                        <div class="isian">
                            <label for="tebak_resistance">Resistance <span class="redup">(boleh kosong)</span></label>
                            <input id="tebak_resistance" name="tebak_resistance" value="{{ old('tebak_resistance') }}" inputmode="decimal" placeholder="Contoh: 5200">
                            @error('tebak_resistance') <span class="salah">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>
            </div>
            <div class="langkah-form pemisah">
                <span class="nomor">3</span>
                <div>
                    <div id="proses" class="tumpuk rapat" hidden aria-live="polite">
                        <b>AI sedang membaca chart…</b>
                        <div class="proses"><i></i></div>
                        <span class="bantu">Biasanya 5–20 detik, kadang sampai satu menit. Jangan tutup halaman ini.</span>
                    </div>
                    @if ($sisaKuota === 0)
                        @if ($premium)
                            <p class="pesan galat">Kuota {{ auth()->user()->batasKuota() }} analisis bulan ini habis. Kuota kembali penuh pada tanggal 1 bulan depan.</p>
                        @else
                            <p class="pesan galat">Kuota hari ini habis. Kuota kembali penuh besok, atau <a href="{{ route('paket.index') }}">upgrade ke premium</a>.</p>
                        @endif
                    @endif
                    <div class="baris">
                        <button class="tombol besar" type="submit" id="tombolKirim" @disabled($sisaKuota === 0)>Jelaskan chart ini</button>
                        @if ($sisaKuota !== 0)
                            <span class="bantu">Memakai 1 dari {{ $sisaKuota }} kuota tersisa {{ $periode === 'bulan' ? 'bulan ini' : 'hari ini' }}.</span>
                        @endif
                    </div>
                    <p class="bantu">Penjelasan AI untuk belajar membaca chart, bukan saran membeli atau menjual.</p>
                </div>
            </div>
        </form>
        <aside class="kartu">
            <div class="kartu-kepala">
                <h2>Analisis terakhir</h2>
                <a href="{{ route('analisis.index') }}" class="kecil">Lihat semua</a>
            </div>
            @if ($terakhir->isEmpty())
                <div class="kosong">
                    @include('partials.ikon', ['nama' => 'chart'])
                    <span>Belum ada analisis. Hasil pertama Anda akan muncul di sini.</span>
                </div>
            @else
                <div class="tumpuk rapat">
                    @foreach ($terakhir as $item)
                        <a class="butir" href="{{ route('analisis.show', $item) }}">
                            <span class="antara">
                                <b>{{ $item->judul() }}</b>
                                @if ($item->status === 'selesai')
                                    <span class="tanda {{ ['naik' => 'naik', 'turun' => 'turun'] [$item->h('tren')] ?? '' }}">{{ ucfirst($item->h('tren', '-')) }}</span>
                                @elseif ($item->status === 'gagal')
                                    <span class="tanda turun">Gagal</span>
                                @endif
                            </span>
                            <span class="bantu">{{ $item->created_at->diffForHumans() }} · {{ $item->labelStatusReview() }}</span>
                        </a>
                    @endforeach
                </div>
            @endif
            @if (! $premium)
                <p class="pesan catatan kecil">Butuh lebih banyak? <a href="{{ route('paket.index') }}">Premium memberi 100 analisis per bulan</a>.</p>
            @endif
        </aside>
    </div>
@endsection
@push('skrip')
    <script>
        const area = document.getElementById('areaUnggah');
        const input = document.getElementById('gambar');
        const tampilkan = () => {
            const file = input.files[0];
            if (!file) return;
            const gambar = document.getElementById('gambarPratinjau');
            gambar.src = URL.createObjectURL(file);
            gambar.hidden = false;
            document.getElementById('petunjukUnggah').hidden = true;
            document.getElementById('namaFile').textContent = file.name + ' · klik untuk mengganti';
        };
        input.addEventListener('change', tampilkan);
        ['dragenter', 'dragover'].forEach(n => area.addEventListener(n, () => area.classList.add('seret')));
        ['dragleave', 'drop'].forEach(n => area.addEventListener(n, () => area.classList.remove('seret')));
        document.getElementById('formAnalisis').addEventListener('submit', () => {
            document.getElementById('proses').hidden = false;
            const tombol = document.getElementById('tombolKirim');
            tombol.disabled = true;
            tombol.textContent = 'Sedang dibaca…';
        });
    </script>
@endpush