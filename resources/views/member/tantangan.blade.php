@extends('layouts.app')

@section('isi')
<div class="kepala">
    <div>
        @if ($aktif)
            <span class="tanda tunggu">Ditutup {{ $aktif->ditutup_pada->translatedFormat('l, j F \p\u\k\u\l H.i') }}</span>
            <h1>{{ $aktif->judul }}</h1>
            <p class="pengantar">Chart sengaja dipotong. Tebak arahnya, lalu lihat lanjutannya setelah tantangan ditutup.</p>
        @else
            <h1>Tantangan mingguan</h1>
            <p class="pengantar">Belum ada tantangan yang sedang berjalan. Mentor sedang menyiapkan yang berikutnya.</p>
        @endif
    </div>
</div>

<div class="{{ $aktif || $selesai->isNotEmpty() ? 'dua' : 'tantangan-kosong' }}">
    @if ($aktif || $selesai->isNotEmpty())
    <div class="tumpuk longgar">
        @if ($aktif)
            <section class="tumpuk">
                <div class="antara bantu"><span>Dibuat {{ $aktif->mentor->name }} (mentor) · disetujui admin</span><span>{{ $aktif->jawaban()->count() }} member sudah menjawab</span></div>
                <img src="{{ route('tantangan.gambar', [$aktif, 'potong']) }}" alt="Chart yang dipotong untuk tantangan {{ $aktif->judul }}" class="gambar-chart">

                @if ($jawabanSaya)
                    <p class="pesan sukses" role="status">Tebakan Anda: <b>{{ \App\Models\Tantangan::ARAH[$jawabanSaya->jawaban] }}</b>{{ $jawabanSaya->alasan ? ' — ' . $jawabanSaya->alasan : '' }}. Poin dihitung otomatis setelah tantangan ditutup.</p>
                @else
                    <form method="POST" action="{{ route('tantangan.jawab', $aktif) }}" class="kartu lapang tumpuk">
                        @csrf
                        <fieldset>
                            <legend>Pilih tebakan Anda</legend>
                            <div class="pilihan">
                                @foreach (\App\Models\Tantangan::ARAH as $nilai => $label)
                                    <label><input type="radio" name="jawaban" value="{{ $nilai }}" @checked(old('jawaban') === $nilai) required><span class="{{ $nilai }}">@include('partials.ikon', ['nama' => $nilai])</span>{{ $label }}</label>
                                @endforeach
                            </div>
                        </fieldset>
                        <div class="isian">
                            <label for="alasan">Alasan singkat <span class="redup">(boleh kosong)</span></label>
                            <input id="alasan" name="alasan" maxlength="200" value="{{ old('alasan') }}" placeholder="Contoh: higher low sejak candle ke-8">
                        </div>
                        <p><button class="tombol besar" type="submit">Kirim tebakan</button></p>
                    </form>
                @endif
            </section>
        @endif

        @if ($selesai->isNotEmpty())
            <section class="kartu lapang tumpuk rapat">
                <h2>Tantangan yang sudah dibuka</h2>
                @foreach ($selesai as $t)
                    @php
                        $milikSaya = $jawabanLama->get($t->id);
                    @endphp
                    <details class="lipat">
                        <summary>
                            <b>{{ $t->judul }}</b>
                            <span class="kecil">Jawaban benar: <b>{{ \App\Models\Tantangan::ARAH[$t->jawaban_benar] }}</b>
                                @if ($milikSaya) · Anda: {{ \App\Models\Tantangan::ARAH[$milikSaya->jawaban] }} ({{ $milikSaya->poin }} poin) @endif
                            </span>
                        </summary>
                        <div class="tumpuk">
                            <img src="{{ route('tantangan.gambar', [$t, 'lanjutan']) }}" alt="Lanjutan chart tantangan {{ $t->judul }}" class="gambar-chart">
                            @if ($t->pembahasan)<p><b>Pembahasan {{ $t->mentor->name }}:</b> {{ $t->pembahasan }}</p>@endif
                        </div>
                    </details>
                @endforeach
            </section>
        @endif
    </div>
    @endif

    <aside class="kartu tumpuk">
        <h2>Papan poin bulan ini</h2>
        <ol class="papan">
            @foreach ($papan as $i => $member)
                @if ($i < 5 || $member->id === auth()->id())
                    <li class="{{ $member->id === auth()->id() ? 'saya' : '' }}">
                        <span>{{ $i + 1 }}. {{ $member->name }}{{ $member->id === auth()->id() ? ' (Anda)' : '' }}</span><b class="angka">{{ $member->total_poin }}</b>
                    </li>
                @endif
            @endforeach
        </ol>
        <p class="bantu">Tebakan benar mendapat {{ \App\Models\Tantangan::POIN_BENAR }} poin, dihitung otomatis saat tantangan ditutup.</p>
    </aside>
</div>
@endsection