@extends('layouts.app')

@section('isi')
<div class="kepala">
    <div>
        <h1>Antrean review</h1>
        <p class="pengantar">Kerjakan dari permintaan yang paling lama. Batas review {{ \App\Models\Review::batasMenit() }} menit sejak masuk antrean.</p>
    </div>
</div>

<div class="ringkasan">
    <div class="{{ $antrean->count() > 0 ? 'perlu' : '' }}"><b>{{ $antrean->count() }}</b><span>Menunggu Anda</span></div>
    <div><b>{{ $selesaiBulanIni }}</b><span>Review selesai bulan ini</span></div>
</div>

<div class="dua kiri">
    <nav aria-label="Daftar antrean" class="kartu tumpuk rapat">
        <h2>Daftar antrean</h2>
        @if ($antrean->isEmpty())
            <p class="redup">Antrean kosong. Review baru akan muncul di sini.</p>
        @else
            <ul class="daftar">
                @foreach ($antrean as $r)
                    <li>
                        <a class="butir" href="{{ route('mentor.index', ['review' => $r->id]) }}" aria-current="{{ $dipilih && $dipilih->id === $r->id ? 'true' : 'false' }}">
                            <b>{{ $r->analisis->judul() }}</b>
                            <span class="bantu">{{ $r->analisis->user->name }} · masuk {{ $r->mulaiDihitung()->diffForHumans() }}</span>
                            @if ($r->terlambat())
                                <span class="tanda turun">Melewati batas</span>
                            @else
                                <span class="bantu">Sisa {{ $r->sisaMenit() }} menit</span>
                            @endif
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </nav>

    <section class="tumpuk longgar">
        @if ($dipilih)
            @php
                $a = $dipilih->analisis;
            @endphp
            <div class="kartu dua rata">
                <div class="layar-chart">
                    <div class="ket"><span>{{ $a->judul() }}</span><span>{{ $a->created_at->translatedFormat('j M Y, H.i') }}</span></div>
                    <img src="{{ $a->urlGambar() }}" alt="Chart {{ $a->judul() }} yang direview">
                </div>
                <div class="tumpuk">
                    <h2>Bacaan AI</h2>
                    <dl class="angka-kunci">
                        <div><dt>Tren</dt><dd>{{ ucfirst($a->h('tren')) }}</dd></div>
                        <div><dt>Support ({{ $a->h('support.kekuatan') }})</dt><dd>{{ \App\Models\Analisis::formatHarga($a->h('support.harga')) }}</dd></div>
                        <div><dt>Resistance ({{ $a->h('resistance.kekuatan') }})</dt><dd>{{ \App\Models\Analisis::formatHarga($a->h('resistance.harga')) }}</dd></div>
                    </dl>
                    <p class="kecil redup">Pola: <b>{{ implode(', ', $a->h('pola', [])) ?: '-' }}</b> · Keyakinan: <b>{{ $a->h('keyakinan') }}</b></p>
                    <p class="isi-teks kecil">{{ $a->h('penjelasan') }}</p>
                </div>
            </div>

            <form method="POST" action="{{ route('mentor.review.update', $dipilih) }}" class="kartu lapang tumpuk">
                @csrf
                @method('PUT')
                <fieldset>
                    <legend>Penilaian Anda</legend>
                    <div class="pilihan">
                        @foreach (\App\Models\Review::PENILAIAN as $nilai => $label)
                            <label><input type="radio" name="penilaian" value="{{ $nilai }}" @checked(old('penilaian') === $nilai) required>{{ $label }}</label>
                        @endforeach
                    </div>
                </fieldset>
                <div class="isian">
                    <label for="catatan">Catatan untuk member</label>
                    <textarea id="catatan" name="catatan" rows="4" aria-describedby="bantu-catatan">{{ old('catatan') }}</textarea>
                    <span class="bantu" id="bantu-catatan">Wajib diisi jika memilih Perlu koreksi atau Salah baca. Pakai aturan yang sama dengan AI: puncak dan lembah, support/resistance minimal 2 sentuhan, tembus dihitung dari penutupan.</span>
                    @error('catatan') <span class="salah">{{ $message }}</span> @enderror
                </div>
                <div class="baris">
                    <button class="tombol besar" type="submit">Kirim review</button>
                    <span class="bantu">Catatan Anda akan tampil pada hasil analisis member.</span>
                </div>
            </form>
        @else
            <div class="tumpuk rapat">
                <h2>Tidak ada yang perlu direview</h2>
                <p class="redup">Sambil menunggu, Anda bisa <a href="{{ route('mentor.tantangan') }}">membuat tantangan mingguan</a>.</p>
            </div>
        @endif
    </section>
</div>
@endsection
