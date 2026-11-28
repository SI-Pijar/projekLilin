<?php

namespace Database\Seeders;

use App\Models\Pembayaran;
use App\Models\User;
use App\Support\Harga;
use Illuminate\Database\Seeder;
use Illuminate\Http\File;
use Illuminate\Support\Facades\Storage;

class PembayaranSeeder extends Seeder
{
    public function run(): void
    {
        $u = User::pluck('id', 'email');
        $admin = $u['rahmat@contoh.id'];

        foreach ([['bima', 1, 3], ['tari', 3, 1]] as [$nama, $bulan, $jamLalu]) {
            Pembayaran::forceCreate([
                'user_id' => $u["{$nama}@contoh.id"],
                'durasi_bulan' => $bulan,
                'jumlah' => Harga::paket($bulan),
                'bukti' => $this->bukti(),
                'status' => 'menunggu',
                'created_at' => now()->subHours($jamLalu),
            ]);
        }

        foreach ([['fajar', 1], ['sari', 3]] as [$nama, $bulan]) {
            Pembayaran::forceCreate([
                'user_id' => $u["{$nama}@contoh.id"],
                'durasi_bulan' => $bulan,
                'jumlah' => Harga::paket($bulan),
                'bukti' => $this->bukti(),
                'status' => 'disetujui',
                'admin_id' => $admin,
                'diperiksa_pada' => now()->subDays(10),
                'created_at' => now()->subDays(10),
            ]);
        }
    }

    private function bukti(): string
    {
        return Storage::disk('local')->putFile('bukti', new File(database_path('seeders/gambar/bukti-transfer.png')));
    }
}
