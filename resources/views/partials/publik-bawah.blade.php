<footer class="publik-bawah">
    <div class="bungkus">
        <div class="kaki-merek">
            @include('partials.logo')
            <p>Alat belajar membaca chart saham dari screenshot. Bukan rekomendasi membeli atau menjual efek.</p>
        </div>
        <nav aria-label="Produk">
            <b>Produk</b>
            <a href="{{ route('beranda') }}#fitur">Cara belajar</a>
            <a href="{{ route('beranda') }}#harga">Harga</a>
        </nav>
        <nav aria-label="Bantuan">
            <b>Bantuan</b>
            <a href="{{ route('bantuan') }}">Pusat bantuan</a>
            <a href="{{ route('syarat') }}">Syarat dan disclaimer</a>
            @if (filled(config('lilin.email_kontak')))
                <a href="mailto:{{ config('lilin.email_kontak') }}">{{ config('lilin.email_kontak') }}</a>
            @endif
        </nav>
        <nav aria-label="Akun">
            <b>Akun</b>
            @auth
                <a href="{{ auth()->user()->halamanAwal() }}">Ruang belajar</a>
            @else
                <a href="{{ route('masuk') }}">Masuk</a>
                <a href="{{ route('daftar') }}">Daftar gratis</a>
            @endauth
        </nav>
    </div>
    <div class="bungkus kaki-bawah">© {{ now()->year }} Lilin</div>
</footer>