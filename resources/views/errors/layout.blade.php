<!DOCTYPE html>
<html lang="id">
<head>
    @include('partials.head', ['title' => $judul])
</head>
<body>
<main class="galat-halaman">
    <div class="kartu">
        @include('partials.logo')
        <span class="kode">Kode {{ $kode }}</span>
        <h1>{{ $judul }}</h1>
        <p class="pengantar">{{ $pesan }}</p>
        <p>
            @auth
                <a class="tombol" href="{{ auth()->user()->halamanAwal() }}">Kembali ke halaman saya</a>
            @else
                <a class="tombol" href="{{ route('beranda') }}">Kembali ke beranda</a>
            @endauth
        </p>
    </div>
</main>
</body>
</html>