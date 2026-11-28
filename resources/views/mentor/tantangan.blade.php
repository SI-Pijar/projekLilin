@extends('layouts.app')

@section('isi')
<div class="kepala">
    <div>
        <h1>Tantangan buatan saya</h1>
        <p class="pengantar">Unggah chart yang sudah dipotong dan lanjutannya. Lanjutan chart disembunyikan dari member sampai tantangan ditutup.</p>
    </div>
</div>

<div class="dua">
    <form method="POST" action="{{ route('mentor.tantangan.store') }}" enctype="multipart/form-data" class="kartu lapang tumpuk longgar">
        @csrf
        <h2>Buat tantangan baru</h2>
        <div class="isian">
            <label for="judul">Judul</label>
            <input id="judul" name="judul" value="{{ old('judul') }}" placeholder="Contoh: Ke mana harga 8 candle berikutnya?" required>
        </div>
        <div class="kisi-2">
            <div class="isian">
                <label for="gambar_potong">Chart yang dipotong</label>
                <input id="gambar_potong" name="gambar_potong" type="file" accept="image/png,image/jpeg,image/webp" required>
            </div>
            <div class="isian">
                <label for="gambar_lanjutan">Chart lengkap (kunci jawaban)</label>
                <input id="gambar_lanjutan" name="gambar_lanjutan" type="file" accept="image/png,image/jpeg,image/webp" required>
            </div>
        </div>
        <fieldset>
            <legend>Jawaban yang benar</legend>
            <div class="pilihan">
                @foreach (\App\Models\Tantangan::ARAH as $nilai => $label)
                    <label><input type="radio" name="jawaban_benar" value="{{ $nilai }}" @checked(old('jawaban_benar') === $nilai) required>{{ $label }}</label>
                @endforeach
            </div>
        </fieldset>
        <div class="isian">
            <label for="pembahasan">Pembahasan <span class="redup">(tampil setelah ditutup)</span></label>
            <textarea id="pembahasan" name="pembahasan" rows="3">{{ old('pembahasan') }}</textarea>
        </div>
        <div class="isian lebar-tanggal">
            <label for="ditutup_pada">Ditutup pada</label>
            <input id="ditutup_pada" name="ditutup_pada" type="datetime-local" value="{{ old('ditutup_pada', now()->next('Friday')->setTime(16, 0)->format('Y-m-d\TH:i')) }}" required>
        </div>
        <p><button class="tombol besar" type="submit">Ajukan ke admin</button></p>
    </form>

    <aside class="kartu tumpuk rapat">
        <h2>Daftar tantangan</h2>
        @if ($daftar->isEmpty())
            <p class="redup">Belum ada tantangan.</p>
        @else
            <ul class="daftar">
                @foreach ($daftar as $t)
                    <li class="tumpuk rapat">
                        <div class="antara"><b>{{ $t->judul }}</b><span class="tanda {{ ['diajukan' => 'tunggu', 'disetujui' => 'info', 'ditolak' => 'turun', 'dibuka' => 'naik'][$t->status] }}">{{ ucfirst($t->status) }}</span></div>
                        <span class="bantu">Tutup {{ $t->ditutup_pada->translatedFormat('j M, H.i') }} · {{ $t->jawaban_count }} jawaban · kunci: {{ \App\Models\Tantangan::ARAH[$t->jawaban_benar] }}</span>
                        @if ($t->bisaDiubah())
                            <div class="baris">
                                <a class="tombol kedua kecil" href="{{ route('mentor.tantangan.edit', $t) }}">{{ $t->status === 'ditolak' ? 'Perbaiki' : 'Edit' }}</a>
                                <form method="POST" action="{{ route('mentor.tantangan.destroy', $t) }}" onsubmit="return confirm('Hapus tantangan ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="tombol bahaya kecil" type="submit">Hapus</button>
                                </form>
                            </div>
                        @else
                            <span class="bantu">Sudah tayang, tidak bisa diubah supaya jawaban member tetap adil.</span>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </aside>
</div>
@endsection
