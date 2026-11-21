@php
    $user = auth()->user();
    $menu = match ($user->role) {
        'mentor' => [
            ['mentor.index', 'Antrean review', 'antrean', $user->reviewDitugaskan()->where('status', 'menunggu')->count()],
            ['mentor.tantangan', 'Tantangan buatan saya', 'bendera', null],
        ],
        'admin' => [
            ['admin.index', 'Pengelolaan', 'kelola', \App\Models\Pembayaran::where('status', 'menunggu')->count() + \App\Models\Tantangan::where('status', 'diajukan')->count()],
            ['admin.pengguna', 'Akun dan peran', 'pengguna', null],
        ],
        default => [
            ['analisis.create', 'Analisis baru', 'tambah', null],
            ['analisis.index', 'Riwayat dan review', 'riwayat', null, ['analisis.index', 'analisis.saham', 'analisis.show']],
            ['tantangan.index', 'Tantangan mingguan', 'bendera', null],
            ['paket.index', 'Paket saya', 'paket', null],
        ],
    };
    $judulMenu = ['mentor' => 'Ruang mentor', 'admin' => 'Admin'][$user->role] ?? 'Belajar';
    $premium = $user->isMember() && $user->isPremium();
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    @include('partials.head')
</head>
<body>
<a class="lompat" href="#konten">Lompat ke konten</a>
<div class="aplikasi">
    <aside class="sisi">
        @include('partials.logo', ['terang' => true])
        <div>
            <div class="judul-menu">{{ $judulMenu }}</div>
            <nav aria-label="Menu utama">
                @foreach ($menu as $butir)
                    @php
                        [$rute, $label, $ikon, $angka] = $butir;
                        $aktif = request()->routeIs(...($butir[4] ?? [$rute, $rute . '.*']));
                    @endphp
                    <a href="{{ route($rute) }}" @if ($aktif) aria-current="page" @endif>
                        @include('partials.ikon', ['nama' => $ikon])
                        {{ $label }}
                        @if ($angka)
                            <span class="hitung" aria-label="{{ $angka }} menunggu">{{ $angka }}</span>
                        @endif
                    </a>
                @endforeach
            </nav>
        </div>
        @if ($user->isMember())
            @php
                $sisa = $user->sisaKuota();
                $batas = max($user->batasKuota(), 1);
            @endphp
            <div class="kuota">
                <span>Kuota {{ $user->periodeKuota() === 'bulan' ? 'bulan ini' : 'hari ini' }}: <b>{{ $sisa }}</b> dari {{ $batas }} tersisa</span>
                <div class="meter" aria-hidden="true"><i style="width: {{ round($sisa / $batas * 100) }}%"></i></div>
                @unless ($premium)
                    <a href="{{ route('paket.index') }}">Naikkan ke premium</a>
                @endunless
            </div>
        @endif
        <div>
            <div class="judul-menu">Akun</div>
            <nav aria-label="Akun">
                <a href="{{ route('profil.edit') }}" @if (request()->routeIs('profil.*')) aria-current="page" @endif>@include('partials.ikon', ['nama' => 'profil']) Profil dan sandi</a>
                <a href="{{ route('syarat') }}" @if (request()->routeIs('syarat')) aria-current="page" @endif>@include('partials.ikon', ['nama' => 'perisai']) Syarat dan disclaimer</a>
                <a href="{{ route('bantuan') }}" @if (request()->routeIs('bantuan')) aria-current="page" @endif>@include('partials.ikon', ['nama' => 'bantuan']) Bantuan</a>
            </nav>
        </div>
        <div class="akun">
            <a class="pemilik" href="{{ route('profil.edit') }}">
                <span class="inisial {{ $premium ? 'premium' : '' }}" aria-hidden="true">{{ $user->inisial() }}</span>
                <span><b>{{ $user->name }}</b><small>{{ $premium ? 'Premium' : $user->labelPeran() }}</small></span>
            </a>
            <form method="POST" action="{{ route('keluar') }}">
                @csrf
                <button class="menu" type="submit">@include('partials.ikon', ['nama' => 'keluar']) Keluar</button>
            </form>
        </div>
    </aside>
    <main class="isi" id="konten" tabindex="-1">
        @include('partials.flash')
        @yield('isi')
    </main>
</div>
@stack('skrip')
</body>
</html>