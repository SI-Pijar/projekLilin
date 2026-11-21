<aside class="akun-sisi">
    <div class="akun-sisi-isi">
        <p class="akun-judul">{{ $judul }}</p>
        <p>{{ $teks }}</p>
        <ul class="cek">
            @foreach ($poin as $butir)
                <li>@include('partials.ikon', ['nama' => 'cek']) {{ $butir }}</li>
            @endforeach
        </ul>
        <figure class="akun-gambar">
            <img src="{{ asset('img/tampilan-hasil.webp') }}" width="1600" height="950" alt="Tampilan halaman hasil analisis di Lilin">
        </figure>
    </div>
</aside>