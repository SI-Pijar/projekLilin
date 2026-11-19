<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Analisis extends Model
{
    use SoftDeletes;

    protected $table = 'analisis';

    protected $fillable = [
        'user_id', 'gambar', 'kode_saham', 'rentang', 'catatan', 'tebakan', 'status',
        'hasil', 'pesan_galat', 'model_ai', 'status_review',
    ];

    protected function casts(): array
    {
        return [
            'hasil' => 'array',
            'tebakan' => 'array',
        ];
    }

    public const RENTANG = ['1H' => 'Per jam', '1D' => 'Harian', '1W' => 'Mingguan'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function review()
    {
        return $this->hasOne(Review::class);
    }

    public function macet(): bool
    {
        return $this->status === 'diproses' && $this->created_at->lt(now()->subMinutes(3));
    }

    public function bisaDiulang(): bool
    {
        return $this->status === 'gagal' || $this->macet();
    }

    public function urlGambar(): string
    {
        return asset('storage/' . $this->gambar);
    }

    public function judul(): string
    {
        return ($this->kode_saham ?: 'Tanpa kode') . ' · ' . strtolower(self::RENTANG[$this->rentang] ?? $this->rentang);
    }

    public function h(string $kunci, $bawaan = null)
    {
        return data_get($this->hasil, $kunci, $bawaan);
    }

    public static function formatHarga($angka): string
    {
        if ($angka === null || $angka === '') {
            return 'tidak terbaca';
        }

        $desimal = floor((float) $angka) == (float) $angka ? 0 : 2;

        return '±' . number_format((float) $angka, $desimal, ',', '.');
    }

    public const TOLERANSI_TEBAKAN = 2.0;

    public function bandingkanTebakan(): ?array
    {
        $t = $this->tebakan;
        if (! $t || $this->status !== 'selesai' || $this->h('status') === 'bukan_chart') {
            return null;
        }

        $harga = function (string $kunci) use ($t) {
            $tebak = $t[$kunci] ?? null;
            $ai = $this->h("{$kunci}.harga");
            if (! is_numeric($tebak)) {
                return ['tebakan' => null, 'ai' => $ai, 'status' => 'kosong', 'selisih' => null];
            }
            if (! is_numeric($ai) || (float) $ai <= 0) {
                return ['tebakan' => (float) $tebak, 'ai' => null, 'status' => 'tak_terbaca', 'selisih' => null];
            }
            $selisih = abs((float) $tebak - (float) $ai) / (float) $ai * 100;

            return [
                'tebakan' => (float) $tebak,
                'ai' => (float) $ai,
                'status' => $selisih <= self::TOLERANSI_TEBAKAN ? 'dekat' : 'jauh',
                'selisih' => $selisih,
            ];
        };

        return [
            'tren' => [
                'tebakan' => $t['tren'] ?? null,
                'ai' => $this->h('tren'),
                'sama' => ($t['tren'] ?? null) === $this->h('tren'),
            ],
            'support' => $harga('support'),
            'resistance' => $harga('resistance'),
        ];
    }

    public function bolehDihapus(): bool
    {
        return $this->status !== 'diproses' && $this->status_review !== 'menunggu';
    }

    public function labelStatusReview(): string
    {
        return match ($this->status_review) {
            'menunggu' => 'Menunggu mentor',
            'direview' => 'Menunggu tanggapan Anda',
            'final' => 'Final',
            default => 'Belum direview',
        };
    }
}
