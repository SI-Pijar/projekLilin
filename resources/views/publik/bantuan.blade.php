@extends(auth()->check() ? 'layouts.app' : 'layouts.publik')

@section('isi')
@guest
    @include('partials.publik-atas')
@endguest

<div class="{{ auth()->check() ? 'tumpuk longgar' : 'bungkus dokumen' }}">
    <div class="kepala">
        <div>
            <h1>Bantuan</h1>
            <p class="pengantar">Jawaban singkat untuk pertanyaan yang paling sering muncul.</p>
        </div>
    </div>

    @php
        $faq = [
            ['Kuota analisis saya habis. Bagaimana?', 'Member biasa mendapat ' . \App\Models\User::KUOTA_HARIAN . ' analisis per hari, dan kuota kembali penuh besok. Analisis yang Anda hapus tetap dihitung, supaya kuota tidak bisa dicurangi. Member premium mendapat ' . config('lilin.kuota_premium_bulanan') . ' analisis per bulan, dan kuota kembali penuh pada tanggal 1.'],
            ['Analisis gagal atau terlalu lama. Apa yang harus saya lakukan?', 'Tunggu sampai satu menit lalu muat ulang halaman hasil. Jika statusnya gagal, tekan tombol coba lagi; analisis yang gagal tidak mengurangi kuota harian Anda di hasil akhir. Pastikan gambar adalah screenshot chart yang jelas, dalam format PNG, JPG, atau WebP, paling besar 5 MB.'],
            ['Bagaimana cara menjadi premium?', config('lilin.pembayaran_simulasi') ? 'Pilih durasi pada Paket saya, pilih bank dan isi nomor rekening, lalu unggah gambar bukti pembayaran. Nama pengirim mengikuti akun dan boleh diubah. Selama mode uji coba tidak perlu transfer uang; premium aktif setelah admin menyetujui.' : 'Buka menu Paket saya, pilih durasi (1 bulan ' . \App\Support\Harga::rupiah(\App\Support\Harga::paket(1)) . ', 3 bulan ' . \App\Support\Harga::rupiah(\App\Support\Harga::paket(3)) . '), transfer ke rekening yang tertera, lalu unggah foto bukti transfer. Admin memeriksa buktinya dan premium aktif setelah disetujui.'],
            [config('lilin.pembayaran_simulasi') ? 'Sudah mengirim bukti tetapi premium belum aktif.' : 'Sudah transfer tetapi premium belum aktif.', 'Admin memeriksa bukti secara manual, jadi butuh waktu. Pastikan foto bukti terbaca jelas dan nominalnya sesuai durasi. Jika bukti ditolak, status di Riwayat pembayaran berubah dan Anda bisa mengirim ulang.'],
            ['Berapa lama mentor memeriksa review saya?', 'Mentor diberi batas ' . \App\Models\Review::batasMenit() . ' menit. Jika terlewat, admin dapat memindahkannya secara manual. Anda juga bisa membatalkan permintaan yang belum dikerjakan setelah batas waktu terlewati.'],
            ['Saya lupa kata sandi.', filled(config('lilin.email_kontak')) ? 'Hubungi kontak di bawah dari alamat email akun Anda. Admin akan memberi sandi sementara; saat masuk Anda diminta langsung menggantinya di halaman Profil.' : 'Hubungi pengelola aplikasi untuk meminta sandi sementara. Saat masuk, Anda diminta langsung menggantinya di halaman Profil.'],
            ['Akun saya dinonaktifkan.', 'Admin dapat menonaktifkan akun yang melanggar syarat pemakaian. Hubungi kontak di bawah dengan menyebut email akun Anda.'],
            ['Bagaimana menghapus data atau akun saya?', filled(config('lilin.email_kontak')) ? 'Anda dapat menghapus tiap analisis sendiri dari halaman hasil; file gambarnya ikut terhapus. Untuk menghapus akun seluruhnya, kirim permintaan lewat kontak di bawah.' : 'Anda dapat menghapus tiap analisis sendiri dari halaman hasil; file gambarnya ikut terhapus. Untuk menghapus akun seluruhnya, hubungi pengelola aplikasi.'],
            ['Apakah Lilin memberi saran beli atau jual saham?', 'Tidak. Lilin adalah alat belajar membaca chart. Baca selengkapnya di halaman Syarat dan disclaimer.'],
        ];
@endphp

    <div class="dokumen-isi">
        @foreach ($faq as $i => [$tanya, $jawab])
            <details class="tanya" @if ($i === 0) open @endif>
                <summary>{{ $tanya }}</summary>
                <p class="isi-teks">{{ $jawab }}</p>
            </details>
        @endforeach

        @if (filled(config('lilin.email_kontak')))
            <section class="kartu tumpuk rapat" aria-labelledby="kontak">
                <h2 id="kontak">Masih butuh bantuan?</h2>
                <p>Kirim email ke <a href="mailto:{{ config('lilin.email_kontak') }}">{{ config('lilin.email_kontak') }}</a>. Sebutkan email akun Anda dan jelaskan masalahnya.</p>
            </section>
        @endif

        <p class="bantu"><a href="{{ route('syarat') }}">Syarat dan disclaimer</a></p>
    </div>
</div>

@guest
    @include('partials.publik-bawah')
@endguest
@endsection
