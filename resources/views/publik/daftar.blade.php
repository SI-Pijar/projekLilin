@extends('layouts.publik')
@section('isi')
    @include('partials.akses-atas')
    <main class="akses">
        <div class="akses-bingkai">
            <div class="akses-isi">
                <div class="akses-judul">
                    <h1>Buat akun gratis</h1>
                    <p>Langsung bisa membaca {{ \App\Models\User::KUOTA_HARIAN }} chart setiap hari.</p>
                </div>
                <form method="POST" action="{{ route('daftar.store') }}" class="akses-form">
                    @csrf
                    @include('partials.flash')
                    <div class="isian">
                        <label for="name">Nama panggilan</label>
                        <input id="name" name="name" value="{{ old('name') }}" autocomplete="nickname" required>
                    </div>
                    <div class="isian">
                        <label for="email">Email</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" placeholder="nama@email.com" required>
                    </div>
                    <div class="isian">
                        <label for="password">Kata sandi</label>
                        <input id="password" name="password" type="password" autocomplete="new-password" required minlength="8" aria-describedby="bantu-sandi">
                        <span class="bantu" id="bantu-sandi">Minimal 8 karakter, berisi huruf dan angka.</span>
                    </div>
                    <div class="isian">
                        <label for="password_confirmation">Ulangi kata sandi</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required>
                    </div>
                    <label class="centang">
                        <input type="checkbox" name="setuju" value="1" required>
                        <span>Saya mengerti Lilin hanya untuk belajar dan bukan saran investasi, serta menyetujui <a href="{{ route('syarat') }}" target="_blank" rel="noopener">syarat dan disclaimer</a>.</span>
                    </label>
                    <button class="tombol besar penuh" type="submit">Buat akun</button>
                </form>
                <p class="akses-alternatif">Sudah punya akun? <a href="{{ route('masuk') }}">Masuk</a></p>
            </div>
            @include('partials.sisi-akun', [
                'judul' => 'Mulai dengan '.\App\Models\User::KUOTA_HARIAN.' chart gratis setiap hari.',
                'teks' => 'Tanpa kartu pembayaran. Naik ke premium kapan saja bila Anda butuh lebih banyak analisis.',
                'poin' => [\App\Models\User::KUOTA_HARIAN.' analisis gratis setiap hari', 'Riwayat dan catatan belajar tersimpan', 'Premium mulai '.\App\Support\Harga::rupiah((int) config('lilin.harga_per_bulan')).' per bulan'],
            ])
        </div>
    </main>
@endsection