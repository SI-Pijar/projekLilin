@extends('layouts.publik')
@php
    use App\Support\Harga;
    $kuotaPremium = config('lilin.kuota_premium_bulanan');
    $batasReview = \App\Models\Review::batasMenit();
@endphp
@section('isi')
    @include('partials.publik-atas')
    <section class="sampul">
        <div class="bungkus">
            @include('partials.flash')
            <div class="sampul-teks">
                <div>
                    <span class="topik">Untuk investor pemula Indonesia</span>
                    <h1>Baca chart.<br>Pahami alasannya.</h1>
                    <p>Unggah screenshot chart dari aplikasi sekuritas Anda, tulis tebakan sendiri, lalu bandingkan dengan penjelasan AI tentang tren, support, resistance, dan pola candlestick.</p>
                    <div class="baris">
                        @auth
                            <a class="tombol besar" href="{{ auth()->user()->halamanAwal() }}">Lanjutkan belajar</a>
                        @else
                            <a class="tombol besar" href="{{ route('daftar') }}">Daftar gratis</a>
                            <a class="tombol besar kedua" href="{{ route('masuk') }}">Masuk</a>
                        @endauth
                    </div>
                </div>
                <dl class="fakta">
                    <div><dt>{{ $kuota }} chart</dt><dd>gratis setiap hari</dd></div>
                    <div><dt>{{ $kuotaPremium }} chart</dt><dd>per bulan untuk premium</dd></div>
                    <div><dt>{{ $batasReview }} menit</dt><dd>batas review mentor</dd></div>
                </dl>
            </div>
            <figure class="layar-produk">
                <img src="{{ asset('img/tampilan-hasil.webp') }}" width="1600" height="950" alt="Tampilan halaman hasil Lilin: chart ABCD harian di kiri, penjelasan AI berisi tren naik, support sekitar 4.790, dan resistance sekitar 5.200 di kanan">
            </figure>
        </div>
    </section>
    <main>
        <section id="fitur" class="bungkus bagian-publik">
            <div class="judul">
                <span class="topik">Cara belajar</span>
                <h2>Latih mata Anda, bukan sekadar membaca jawaban</h2>
            </div>
            <div class="fitur">
                <div class="fitur-teks">
                    <span class="nomor">1</span>
                    <h3>Tebak dulu sebelum melihat jawaban</h3>
                    <p>Tarik screenshot chart ke halaman Analisis baru, lalu pilih arah tren dan perkirakan support serta resistance menurut Anda. Kode saham dan rentang waktu membantu Anda melacaknya nanti.</p>
                    <ul class="cek">
                        <li>@include('partials.ikon', ['nama' => 'cek']) PNG, JPG, atau WEBP sampai 5 MB</li>
                        <li>@include('partials.ikon', ['nama' => 'cek']) Dari aplikasi sekuritas mana pun</li>
                    </ul>
                </div>
                <img class="fitur-gambar potong" src="{{ asset('img/tampilan-unggah.webp') }}" width="900" height="1149" loading="lazy" alt="Formulir Analisis baru dengan area unggah gambar, pilihan arah tren, dan isian support serta resistance">
            </div>
            <div class="fitur">
                <div class="fitur-teks">
                    <span class="nomor">2</span>
                    <h3>Lihat di mana bacaan Anda meleset</h3>
                    <p>Setelah Google Gemini membaca gambar yang sama, tebakan Anda dan bacaan AI ditampilkan berdampingan. Selisih harga sampai {{ rtrim(rtrim(number_format(\App\Models\Analisis::TOLERANSI_TEBAKAN, 1, ",", ""), "0"), ",") }}% dianggap dekat.</p>
                    <p class="bantu">AI juga bisa keliru. Karena itu setiap hasil menyebutkan apa yang tidak bisa dibaca dari gambar.</p>
                </div>
                <img class="fitur-gambar" src="{{ asset('img/tampilan-tebakan.webp') }}" width="1200" height="415" loading="lazy" alt="Tabel perbandingan tebakan pengguna dan hasil AI untuk tren, support, dan resistance">
            </div>
            <div class="fitur">
                <div class="fitur-teks">
                    <span class="nomor">3</span>
                    <h3>Pantau pergeseran tiap saham</h3>
                    <p>Setiap kode saham punya halaman sendiri yang menunjukkan bagaimana support, resistance, dan tren hasil analisis Anda bergeser dari waktu ke waktu.</p>
                </div>
                <img class="fitur-gambar" src="{{ asset('img/tampilan-saham.webp') }}" width="1200" height="478" loading="lazy" alt="Grafik garis support dan resistance saham ABCD dari enam analisis">
            </div>
            <div class="fitur-tambahan">
                <div>
                    <h3>Dikoreksi mentor</h3>
                    <p>Member premium bisa meminta mentor menilai bacaan AI: benar, perlu koreksi, atau salah baca, lengkap dengan catatannya.</p>
                </div>
                <div>
                    <h3>Tantangan mingguan</h3>
                    <p>Chart sengaja dipotong. Tebak arah lanjutannya, kumpulkan poin, dan lihat pembahasan mentor setelah tantangan ditutup.</p>
                </div>
                <div>
                    <h3>Catatan belajar pribadi</h3>
                    <p>Simpan apa yang Anda pelajari di setiap chart. Catatan hanya bisa dilihat oleh Anda.</p>
                </div>
            </div>
        </section>
        <section id="harga" class="lembar-terang">
            <div class="bungkus bagian-publik">
                <div class="judul">
                    <span class="topik">Harga</span>
                    <h2>Mulai gratis, naikkan saat butuh</h2>
                    <p class="pengantar">Premium dapat dibayar untuk 1 bulan atau 3 bulan sekaligus ({{ Harga::rupiah(Harga::paket(3)) }}).</p>
                </div>
                <div class="paket">
                    <article class="kartu lapang">
                        <h3>Biasa</h3>
                        <p class="harga">Gratis</p>
                        <ul>
                            <li>@include('partials.ikon', ['nama' => 'cek']) {{ $kuota }} analisis AI per hari</li>
                            <li>@include('partials.ikon', ['nama' => 'cek']) Tantangan chart mingguan</li>
                            <li>@include('partials.ikon', ['nama' => 'cek']) Riwayat 7 hari terakhir</li>
                            <li class="tidak">@include('partials.ikon', ['nama' => 'silang']) Review mentor</li>
                        </ul>
                        <a class="tombol kedua" href="{{ route('daftar') }}">Daftar gratis</a>
                    </article>
                    <article class="kartu lapang utama">
                        <h3>Premium</h3>
                        <p class="harga">{{ Harga::rupiah(Harga::paket(1)) }} <small>/ bulan</small></p>
                        <ul>
                            <li>@include('partials.ikon', ['nama' => 'cek']) {{ $kuotaPremium }} analisis AI per bulan</li>
                            <li>@include('partials.ikon', ['nama' => 'cek']) Review mentor, batas {{ $batasReview }} menit</li>
                            <li>@include('partials.ikon', ['nama' => 'cek']) Riwayat lengkap tanpa batas waktu</li>
                            <li>@include('partials.ikon', ['nama' => 'cek']) Tantangan chart mingguan</li>
                        </ul>
                        <a class="tombol" href="{{ route('daftar') }}">Daftar, lalu pilih premium</a>
                    </article>
                </div>
            </div>
        </section>
        <section id="tanya" class="bungkus bagian-publik tanya-singkat">
            <div class="judul">
                <span class="topik">Pertanyaan umum</span>
                <h2>Sebelum Anda mulai</h2>
                <p class="pengantar">Belum terjawab? Buka <a href="{{ route('bantuan') }}">halaman Bantuan</a>.</p>
            </div>
            <div class="tumpuk rapat">
                <details class="tanya" open>
                    <summary>Apakah Lilin memberi saran membeli atau menjual saham?</summary>
                    <p>Tidak. Lilin adalah alat belajar membaca chart. Semua penjelasan adalah materi edukasi, dan keputusan investasi sepenuhnya milik Anda.</p>
                </details>
                <details class="tanya">
                    <summary>Chart dari aplikasi apa yang bisa dibaca?</summary>
                    <p>Screenshot chart candlestick dari aplikasi sekuritas mana pun, dalam format PNG, JPG, atau WEBP sampai 5 MB. Pastikan angka harga di sumbu kanan terlihat jelas.</p>
                </details>
                <details class="tanya">
                    <summary>Seberapa akurat bacaan AI?</summary>
                    <p>Angka harga dibaca dari gambar sehingga bisa meleset sedikit, dan AI tidak melihat berita atau laporan keuangan. Karena itu setiap hasil bisa diperiksa mentor.</p>
                </details>
                <details class="tanya">
                    <summary>Apa yang terjadi dengan gambar yang saya unggah?</summary>
                    <p>Gambar dikirim ke Google Gemini untuk dibaca dan disimpan dengan nama acak. Anda bisa menghapus analisis kapan saja, dan gambarnya ikut terhapus.</p>
                </details>
            </div>
        </section>
        <section class="ajakan">
            <div class="bungkus">
                <div>
                    <h2>Coba baca chart pertama Anda hari ini</h2>
                    <p>Gratis {{ $kuota }} chart per hari, tanpa kartu kredit.</p>
                </div>
                <a class="tombol besar" href="{{ auth()->check() ? auth()->user()->halamanAwal() :route('daftar') }}">{{ auth()->check() ? 'Lanjutkan belajar' : 'Daftar gratis' }}</a>
            </div>
        </section>
    </main>
    @include('partials.publik-bawah')
@endsection