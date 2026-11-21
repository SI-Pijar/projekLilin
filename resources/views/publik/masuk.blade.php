@extends('layouts.publik')
@section('isi')
    @include('partials.akses-atas')
    <main class="akses">
        <div class="akses-bingkai">
            <div class="akses-isi">
                <div class="akses-judul">
                    <h1>Masuk ke Lilin</h1>
                    <p>Satu halaman masuk untuk member, mentor, dan admin.</p>
                </div>
                <form method="POST" action="{{ route('masuk.store') }}" class="akses-form">
                    @csrf
                    @include('partials.flash')
                    <div class="isian">
                        <label for="email">Email</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" placeholder="nama@email.com" required>
                    </div>
                    <div class="isian">
                        <div class="antara">
                            <label for="password">Kata sandi</label>
                            <a class="kecil" href="{{ route('bantuan') }}">Lupa kata sandi?</a>
                        </div>
                        <input id="password" name="password" type="password" autocomplete="current-password" required>
                    </div>
                    <label class="centang"><input type="checkbox" name="ingat" value="1">Ingat saya di perangkat ini</label>
                    <button class="tombol besar penuh" type="submit">Masuk</button>
                </form>
                <p class="akses-alternatif">Belum punya akun? <a href="{{ route('daftar') }}">Daftar gratis</a></p>
            </div>
            @include('partials.sisi-akun', [
                'judul' => 'Tebak dulu, lalu bandingkan dengan penjelasan AI.',
                'teks' => 'Riwayat analisis, catatan belajar, dan hasil review mentor Anda tersimpan di akun.',
                'poin' => ['Bandingkan tebakan Anda dengan penjelasan AI', 'Minta review dari mentor', 'Ikuti tantangan mingguan'],
            ])
        </div>
    </main>
@endsection