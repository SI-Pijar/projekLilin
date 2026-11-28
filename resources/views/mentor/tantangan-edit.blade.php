@extends('layouts.app')

@section('isi')
<div class="kepala">
    <div>
        <a href="{{ route('mentor.tantangan') }}" class="kembali">@include('partials.ikon', ['nama' => 'kembali']) Tantangan buatan saya</a>
        <h1>{{ $tantangan->status === 'ditolak' ? 'Perbaiki tantangan' : 'Edit tantangan' }}</h1>
        <p class="pengantar">
            @if ($tantangan->status === 'ditolak')
                Tantangan ini ditolak admin. Setelah disimpan, tantangan otomatis diajukan ulang.
            @else
                Tantangan masih menunggu persetujuan admin, jadi semuanya masih boleh diubah.
            @endif
        </p>
    </div>
</div>

<form method="POST" action="{{ route('mentor.tantangan.update', $tantangan) }}" enctype="multipart/form-data" class="kartu lapang tumpuk longgar">
    @csrf
    @method('PUT')
    <div class="isian">
        <label for="judul">Judul</label>
        <input id="judul" name="judul" value="{{ old('judul', $tantangan->judul) }}" required>
    </div>

    <div class="dua rata">
        <div class="tumpuk rapat">
            <span class="label">Chart yang dipotong (sekarang)</span>
            <img src="{{ route('tantangan.gambar', [$tantangan, 'potong']) }}" alt="Chart dipotong saat ini" class="gambar-chart">
            <div class="isian">
                <label for="gambar_potong">Ganti gambar <span class="redup">(boleh kosong)</span></label>
                <input id="gambar_potong" name="gambar_potong" type="file" accept="image/png,image/jpeg,image/webp">
            </div>
        </div>
        <div class="tumpuk rapat">
            <span class="label">Chart lengkap / kunci (sekarang)</span>
            <img src="{{ route('tantangan.gambar', [$tantangan, 'lanjutan']) }}" alt="Chart lengkap saat ini" class="gambar-chart">
            <div class="isian">
                <label for="gambar_lanjutan">Ganti gambar <span class="redup">(boleh kosong)</span></label>
                <input id="gambar_lanjutan" name="gambar_lanjutan" type="file" accept="image/png,image/jpeg,image/webp">
            </div>
        </div>
    </div>

    <fieldset>
        <legend>Jawaban yang benar</legend>
        <div class="pilihan">
            @foreach (\App\Models\Tantangan::ARAH as $nilai => $label)
                <label><input type="radio" name="jawaban_benar" value="{{ $nilai }}" @checked(old('jawaban_benar', $tantangan->jawaban_benar) === $nilai) required>{{ $label }}</label>
            @endforeach
        </div>
    </fieldset>

    <div class="isian">
        <label for="pembahasan">Pembahasan <span class="redup">(tampil setelah ditutup)</span></label>
        <textarea id="pembahasan" name="pembahasan" rows="3">{{ old('pembahasan', $tantangan->pembahasan) }}</textarea>
    </div>
    <div class="isian lebar-tanggal">
        <label for="ditutup_pada">Ditutup pada</label>
        <input id="ditutup_pada" name="ditutup_pada" type="datetime-local" value="{{ old('ditutup_pada', $tantangan->ditutup_pada->format('Y-m-d\TH:i')) }}" required>
    </div>

    <div class="baris">
        <button class="tombol besar" type="submit">{{ $tantangan->status === 'ditolak' ? 'Simpan dan ajukan ulang' : 'Simpan perubahan' }}</button>
        <a href="{{ route('mentor.tantangan') }}">Batal</a>
    </div>
</form>
@endsection