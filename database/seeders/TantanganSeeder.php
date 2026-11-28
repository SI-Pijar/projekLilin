<?php

namespace Database\Seeders;

use App\Models\JawabanTantangan;
use App\Models\Tantangan;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Http\File;
use Illuminate\Support\Facades\Storage;

class TantanganSeeder extends Seeder
{
    public function run(): void
    {
        $u = User::pluck('id', 'email');

        $lalu = Tantangan::forceCreate([
            'mentor_id' => $u['dina@contoh.id'],
            'judul' => 'Minggu lalu: lanjut naik atau berbalik?',
            'gambar_potong' => $this->simpan('tantangan-lalu-potong.png'),
            'gambar_lanjutan' => $this->simpan('tantangan-lalu-lanjutan.png'),
            'jawaban_benar' => 'turun',
            'pembahasan' => 'Puncak terakhir lebih rendah dari puncak sebelumnya (lower high), tanda awal tren berbalik. Setelah itu harga turun beberapa candle berturut-turut.',
            'ditutup_pada' => now()->subDays(2)->setTime(16, 0),
            'status' => 'dibuka',
            'created_at' => now()->subDays(7),
        ]);
        foreach ([['fajar', 'turun'], ['sari', 'turun'], ['bima', 'naik'], ['nadia', 'turun'], ['tari', 'datar']] as [$nama, $jawab]) {
            JawabanTantangan::forceCreate([
                'tantangan_id' => $lalu->id, 'user_id' => $u["{$nama}@contoh.id"], 'jawaban' => $jawab,
                'poin' => $jawab === 'turun' ? Tantangan::POIN_BENAR : 0, 'created_at' => now()->subDays(4),
            ]);
        }

        $aktif = Tantangan::forceCreate([
            'mentor_id' => $u['dina@contoh.id'],
            'judul' => 'Ke mana harga 8 candle berikutnya?',
            'gambar_potong' => $this->simpan('tantangan-potong.png'),
            'gambar_lanjutan' => $this->simpan('tantangan-lanjutan.png'),
            'jawaban_benar' => 'naik',
            'pembahasan' => 'Setelah double bottom di sekitar 4.790, lembah terus naik. Harga lalu menembus puncak tengah dan tren naik berlanjut.',
            'ditutup_pada' => now()->next('Friday')->setTime(16, 0),
            'status' => 'disetujui',
        ]);
        foreach ([['fajar', 'naik', 'Higher low sejak candle ke-8'], ['sari', 'datar', null]] as [$nama, $jawab, $alasan]) {
            JawabanTantangan::forceCreate(['tantangan_id' => $aktif->id, 'user_id' => $u["{$nama}@contoh.id"], 'jawaban' => $jawab, 'alasan' => $alasan]);
        }

        Tantangan::forceCreate([
            'mentor_id' => $u['dina@contoh.id'],
            'judul' => 'Double bottom atau lanjut turun?',
            'gambar_potong' => $this->simpan('tantangan-lalu-potong.png'),
            'gambar_lanjutan' => $this->simpan('tantangan-lalu-lanjutan.png'),
            'jawaban_benar' => 'naik',
            'pembahasan' => 'Kunci jawaban perlu dicek ulang: lanjutan chart sebenarnya turun.',
            'ditutup_pada' => now()->addDays(10)->setTime(16, 0),
            'status' => 'ditolak',
        ]);

        Tantangan::forceCreate([
            'mentor_id' => $u['yoga@contoh.id'],
            'judul' => 'Tembus resistance atau tertahan?',
            'gambar_potong' => $this->simpan('tantangan-potong.png'),
            'gambar_lanjutan' => $this->simpan('tantangan-lanjutan.png'),
            'jawaban_benar' => 'naik',
            'ditutup_pada' => now()->addDays(14)->setTime(16, 0),
            'status' => 'diajukan',
        ]);
    }

    private function simpan(string $nama): string
    {
        return Storage::disk('local')->putFile('tantangan', new File(database_path('seeders/gambar/' . $nama)));
    }
}