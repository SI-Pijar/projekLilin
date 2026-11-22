<?php

namespace Database\Seeders;

use App\Models\Analisis;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Http\File;
use Illuminate\Support\Facades\Storage;

class AnalisisSeeder extends Seeder
{
    public function run(): void
    {
        $u = User::pluck('id', 'email');
        $dina = $u['dina@contoh.id'];
        $yoga = $u['yoga@contoh.id'];
        $laras = $u['laras@contoh.id'];

        $a = $this->analisis($u['fajar@contoh.id'], 'chart-naik.png', 'ABCD', '1D', $this->hasilNaik(), 'direview', now()->subHours(3));
        $a->update(['tebakan' => ['tren' => 'naik', 'support' => 4770, 'resistance' => 5200]]);
        $a->update(['catatan' => 'Bacaan saya sebelum lihat AI: tren naik. Saya sempat salah mengira 4.770 sebagai support, padahal itu hanya sumbu, bukan area yang disentuh dua kali.']);
        Review::forceCreate(['analisis_id' => $a->id, 'mentor_id' => $dina, 'status' => 'selesai', 'penilaian' => 'koreksi',
            'catatan' => 'Tren naik dan higher low sudah tepat. Support lebih pas di 4.790–4.800 karena ada dua lembah di area itu, bukan 4.770 yang hanya sumbu. Resistance 5.200 masih lemah karena baru disentuh sekali.',
            'direview_pada' => now()->subHours(1), 'created_at' => now()->subHours(3)]);

        $a = $this->analisis($u['fajar@contoh.id'], 'chart-turun.png', 'WXYZ', '1W', $this->hasilTurun(), 'menunggu', now()->subHours(2));
        Review::forceCreate(['analisis_id' => $a->id, 'mentor_id' => $dina, 'status' => 'menunggu', 'created_at' => now()->subMinutes(5), 'ditugaskan_pada' => now()->subMinutes(5)]);

        $a = $this->analisis($u['sari@contoh.id'], 'chart-datar.png', 'KLMN', '1H', $this->hasilDatar(), 'menunggu', now()->subHours(5));
        Review::forceCreate(['analisis_id' => $a->id, 'mentor_id' => $dina, 'status' => 'menunggu', 'created_at' => now()->subMinutes(10), 'ditugaskan_pada' => now()->subMinutes(10)]);

        $a = $this->analisis($u['fajar@contoh.id'], 'chart-campur.png', 'PQRS', '1D', $this->hasilCampur(), 'direview', now()->subDay());
        $r = Review::forceCreate(['analisis_id' => $a->id, 'mentor_id' => $dina, 'status' => 'selesai', 'penilaian' => 'koreksi',
            'catatan' => 'Ini bukan tren turun murni. Dua lembah terakhir sudah sejajar di sekitar 640, jadi lebih tepat dibaca double bottom yang belum terkonfirmasi.',
            'direview_pada' => now()->subHours(20), 'created_at' => now()->subDay()]);

        $riwayat = [
            [$u['sari@contoh.id'], $yoga, 'benar'],
            [$u['sari@contoh.id'], $yoga, 'koreksi'],
            [$u['fajar@contoh.id'], $yoga, 'benar'],
            [$u['fajar@contoh.id'], $dina, 'benar'],
            [$u['sari@contoh.id'], $dina, 'koreksi'],
            [$u['sari@contoh.id'], $laras, 'salah'],
            [$u['fajar@contoh.id'], $laras, 'koreksi'],
            [$u['sari@contoh.id'], $laras, 'koreksi'],
        ];
        foreach ($riwayat as $i => [$member, $mentor, $nilai]) {
            $waktu = now()->subDays(3 + $i);
            $a = $this->analisis($member, ['chart-naik.png', 'chart-turun.png', 'chart-datar.png'][$i % 3], ['ABCD', 'WXYZ', 'KLMN'][$i % 3], '1D',
                [$this->hasilNaik(), $this->hasilTurun(), $this->hasilDatar()][$i % 3], 'final', $waktu);
            $r = Review::forceCreate(['analisis_id' => $a->id, 'mentor_id' => $mentor, 'status' => 'selesai', 'penilaian' => $nilai,
                'catatan' => $nilai === 'benar' ? null : 'Koreksi pada area support sesuai aturan minimal dua sentuhan.',
                'direview_pada' => $waktu->copy()->addHours(4), 'created_at' => $waktu]);
        }

        foreach ([[21, 'datar', 4620, 5050], [14, 'naik', 4700, 5120], [7, 'naik', 4750, 5160]] as [$hari, $tren, $support, $resistance]) {
            $hasil = $this->hasilNaik();
            $hasil['tren'] = $tren;
            $hasil['support']['harga'] = $support;
            $hasil['resistance']['harga'] = $resistance;
            $hasil['pola'] = $tren === 'datar' ? ['sideways'] : ['higher low'];
            $this->analisis($u['fajar@contoh.id'], 'chart-naik.png', 'ABCD', '1D', $hasil, 'tidak_diminta', now()->subDays($hari));
        }

        $this->analisis($u['nadia@contoh.id'], 'chart-naik.png', 'ABCD', '1D', $this->hasilNaik(), 'tidak_diminta', now()->subMinutes(40));
    }

    private function analisis(int $userId, string $gambar, string $kode, string $rentang, array $hasil, string $statusReview, $waktu): Analisis
    {
        $path = Storage::disk('public')->putFile('chart', new File(database_path('seeders/gambar/' . $gambar)));

        return Analisis::forceCreate([
            'user_id' => $userId, 'gambar' => $path, 'kode_saham' => $kode, 'rentang' => $rentang,
            'status' => 'selesai', 'hasil' => $hasil, 'model_ai' => 'data-contoh',
            'status_review' => $statusReview, 'created_at' => $waktu, 'updated_at' => $waktu,
        ]);
    }

    private function hasilNaik(): array
    {
        return [
            'status' => 'ok', 'tren' => 'naik',
            'support' => ['harga' => 4790, 'kekuatan' => 'kuat'],
            'resistance' => ['harga' => 5200, 'kekuatan' => 'lemah'],
            'pola' => ['higher low'],
            'channel' => ['ada' => true, 'arah' => 'naik', 'posisi_harga' => 'dekat garis atas'],
            'keyakinan' => 'sedang',
            'penjelasan' => 'Harga turun sampai sekitar 4.790 lalu memantul. Sejak itu setiap lembah (titik terendah) lebih tinggi dari sebelumnya, pola yang disebut higher low, dan setiap puncak juga makin tinggi. Menurut Dow Theory ini tanda tren naik. Harga kini dekat garis atas channel naik di sekitar 5.200.',
            'skenario_naik' => 'Jika ada candle yang ditutup di atas 5.200, area itu tembus dan tren naik berlanjut.',
            'skenario_turun' => 'Jika gagal menembus 5.200, wajar bila harga turun ke garis bawah channel untuk menguji ulang.',
            'tidak_terbaca' => 'Volume transaksi tidak terlihat. Angka harga dibaca dari sumbu, jadi bisa meleset sedikit.',
        ];
    }

    private function hasilTurun(): array
    {
        return [
            'status' => 'ok', 'tren' => 'turun',
            'support' => ['harga' => 2850, 'kekuatan' => 'lemah'],
            'resistance' => ['harga' => 3150, 'kekuatan' => 'kuat'],
            'pola' => ['lower high'],
            'channel' => ['ada' => true, 'arah' => 'turun', 'posisi_harga' => 'di tengah'],
            'keyakinan' => 'tinggi',
            'penjelasan' => 'Puncak dan lembah sama-sama makin rendah, jadi trennya turun. Area 3.150 sudah dua kali menahan kenaikan sehingga menjadi resistance kuat.',
            'skenario_naik' => 'Jika harga ditutup di atas 3.150, pola turun mulai patah.',
            'skenario_turun' => 'Jika lembah baru terbentuk di bawah 2.850, tren turun berlanjut.',
            'tidak_terbaca' => 'Volume tidak terlihat.',
        ];
    }

    private function hasilDatar(): array
    {
        return [
            'status' => 'ok', 'tren' => 'datar',
            'support' => ['harga' => 1480, 'kekuatan' => 'kuat'],
            'resistance' => ['harga' => 1540, 'kekuatan' => 'kuat'],
            'pola' => ['doji'],
            'channel' => ['ada' => false, 'arah' => null, 'posisi_harga' => null],
            'keyakinan' => 'sedang',
            'penjelasan' => 'Harga bolak-balik di rentang 1.480 sampai 1.540. Puncak dan lembah tidak konsisten naik atau turun, jadi trennya datar (sideways). Candle terakhir doji, artinya pembeli dan penjual sedang seimbang.',
            'skenario_naik' => 'Jika ditutup di atas 1.540, harga keluar dari rentang ke atas.',
            'skenario_turun' => 'Jika ditutup di bawah 1.480, harga keluar dari rentang ke bawah.',
            'tidak_terbaca' => 'Rentang per jam membuat angka sumbu kecil dan agak sulit dibaca.',
        ];
    }

    private function hasilCampur(): array
    {
        return [
            'status' => 'ok', 'tren' => 'turun',
            'support' => ['harga' => 640, 'kekuatan' => 'lemah'],
            'resistance' => ['harga' => 760, 'kekuatan' => 'lemah'],
            'pola' => ['lower low'],
            'channel' => ['ada' => true, 'arah' => 'turun', 'posisi_harga' => 'dekat garis bawah'],
            'keyakinan' => 'rendah',
            'penjelasan' => 'Harga bergerak turun dengan lembah terakhir di sekitar 640. Gambar cukup kecil sehingga beberapa angka sumbu sulit dibaca.',
            'skenario_naik' => 'Jika ditutup di atas 760, tekanan turun melemah.',
            'skenario_turun' => 'Jika ditutup di bawah 640, tren turun berlanjut.',
            'tidak_terbaca' => 'Sebagian angka sumbu terpotong.',
        ];
    }
}

