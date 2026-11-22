@extends(auth()->check() ? 'layouts.app' : 'layouts.publik')

@section('isi')
@guest
    @include('partials.publik-atas')
@endguest

<div class="{{ auth()->check() ? 'tumpuk longgar' : 'bungkus dokumen' }}">
    <div class="kepala">
        <div>
            <h1>Syarat dan disclaimer</h1>
            <p class="pengantar">Isinya pendek dan menjelaskan apa yang Lilin lakukan, dan tidak lakukan, dengan chart dan data Anda.</p>
            <p class="bantu">Diperbarui 9 November 2026</p>
        </div>
    </div>

    <div class="kartu lapang dokumen-isi">
        <section class="pemisah" aria-labelledby="s1">
            <h2 id="s1">1. Lilin adalah alat belajar, bukan saran investasi</h2>
            <p class="isi-teks">Lilin membantu Anda belajar membaca chart saham: tren, support, resistance, dan pola candlestick. Semua penjelasan adalah materi edukasi.

Lilin tidak memberi rekomendasi membeli, menjual, atau menahan saham, dan tidak memberi target harga. Lilin bukan penasihat investasi atau perusahaan efek berizin OJK.

Keputusan investasi sepenuhnya milik Anda. Harga saham bisa turun, dan Anda bisa kehilangan sebagian atau seluruh modal.</p>
        </section>

        <section class="pemisah" aria-labelledby="s2">
            <h2 id="s2">2. Bacaan AI dan mentor bisa keliru</h2>
            <p class="isi-teks">Penjelasan dibuat oleh model AI dari gambar yang Anda unggah. Angka harga dibaca dari sumbu gambar dan bisa meleset. AI tidak melihat volume yang tidak tampil di gambar, berita, atau laporan keuangan.

Review mentor membantu mengoreksi, tetapi juga bukan jaminan. Karena itu setiap hasil memuat bagian "yang tidak bisa dibaca dari gambar ini". Periksa catatan mentor bersama hasil AI.</p>
        </section>

        <section class="pemisah" aria-labelledby="s3">
            <h2 id="s3">3. Gambar chart dan data Anda</h2>
            <p class="isi-teks">Gambar yang Anda unggah dikirim ke layanan Google Gemini supaya bisa dibaca AI, dan diproses Google sesuai kebijakan mereka.

Gambar juga disimpan di server Lilin dengan nama file acak yang sulit ditebak. Lilin hanya menampilkannya kepada Anda; mentor yang ditugaskan saat Anda meminta review. Catatan: tautan langsung ke berkas gambar tidak meminta login, jadi siapa pun yang memegang tautan persisnya dapat membukanya. Jangan bagikan tautan gambar Anda ke orang lain.

Anda bisa menghapus analisis kapan saja, kecuali yang sedang diperiksa mentor. File gambarnya ikut terhapus. Catatan ringkas tanpa gambar tetap tersimpan, supaya kuota analisis tidak bisa dicurangi.

Jangan unggah screenshot yang memperlihatkan saldo, nama akun, nomor rekening, atau data pribadi lain. Potong dulu bagian itu sebelum mengunggah.

Bukti transfer pembayaran disimpan terpisah dan hanya bisa dilihat admin. Kata sandi disimpan dalam bentuk acak (hash), jadi tidak bisa dibaca siapa pun, termasuk admin. Lilin tidak menjual data Anda.</p>
        </section>

        <section class="pemisah" aria-labelledby="s5">
            <h2 id="s5">4. Paket, review, dan pembayaran</h2>
            <p class="isi-teks">Member biasa mendapat {{ \App\Models\User::KUOTA_HARIAN }} analisis per hari. Member premium mendapat {{ config('lilin.kuota_premium_bulanan') }} analisis per bulan (kuota kembali penuh tanggal 1), review mentor dan riwayat lengkap.

@if (config('lilin.pembayaran_simulasi'))
Pembayaran premium saat ini berjalan dalam mode uji coba: tidak ada uang yang dipindahkan dan tidak ada rekening tujuan. Gunakan data pengirim dan gambar bukti contoh, bukan data perbankan pribadi. Admin memeriksa pengajuan, dan premium aktif setelah disetujui.
@else
Pembayaran premium lewat transfer. Admin memeriksa bukti transfer, dan premium aktif setelah disetujui.
@endif

Mentor diberi batas {{ \App\Models\Review::batasMenit() }} menit untuk menilai satu review. Kalau terlewat, admin dapat memindahkan penugasan secara manual.</p>
        </section>

        <section class="pemisah" aria-labelledby="s6">
            <h2 id="s6">5. Pengelola dan batasan layanan</h2>
            <p class="isi-teks">Lilin dikelola oleh tim Lilin. Jangan memasukkan data yang Anda anggap rahasia ke dalam gambar chart. Pertanyaan dan permintaan terkait data Anda dapat disampaikan lewat halaman <a href="{{ route('bantuan') }}">Bantuan</a>.</p>
        </section>

        <p class="bantu">Dengan memakai Lilin, Anda dianggap sudah membaca dan menyetujui halaman ini.</p>
    </div>
</div>

@guest
    @include('partials.publik-bawah')
@endguest
@endsection