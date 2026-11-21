<header class="publik-atas">
    <div class="bungkus">
        @include('partials.logo')
        <nav aria-label="Bagian halaman">
            <a href="{{ route('beranda') }}#fitur">Cara belajar</a>
            <a href="{{ route('beranda') }}#harga">Harga</a>
            <a href="{{ route('bantuan') }}">Bantuan</a>
        </nav>
        <div class="akun-tamu">
            @auth
                <span>Masuk sebagai <b>{{ auth()->user()->name }}</b></span>
                <form method="POST" action="{{ route('keluar') }}">
                    @csrf
                    <button class="tautan-keluar" type="submit">Keluar</button>
                </form>
                <a class="tombol gelap" href="{{ auth()->user()->halamanAwal() }}">Buka ruang belajar</a>
            @else
                <a href="{{ route('masuk') }}">Masuk</a>
                <a class="tombol gelap" href="{{ route('daftar') }}">Daftar gratis</a>
            @endauth
        </div>
    </div>
</header>