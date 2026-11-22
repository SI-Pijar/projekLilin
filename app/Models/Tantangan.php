<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tantangan extends Model
{
    protected $table = 'tantangan';

    protected $fillable = [
        'mentor_id', 'judul', 'gambar_potong', 'gambar_lanjutan',
        'jawaban_benar', 'pembahasan', 'ditutup_pada', 'status',
    ];

    protected function casts(): array
    {
        return [
            'ditutup_pada' => 'datetime',
        ];
    }

    public const ARAH = ['naik' => 'Naik', 'datar' => 'Datar', 'turun' => 'Turun'];

    public const POIN_BENAR = 10;

    public function mentor()
    {
        return $this->belongsTo(User::class, 'mentor_id');
    }

    public function jawaban()
    {
        return $this->hasMany(JawabanTantangan::class);
    }

    public function bisaDiubah(): bool
    {
        return in_array($this->status, ['diajukan', 'ditolak'], true);
    }

    public function masihBuka(): bool
    {
        return $this->status === 'disetujui' && $this->ditutup_pada->isFuture();
    }

    public function bukaJikaSudahDitutup(): void
    {
        if ($this->status !== 'disetujui' || $this->ditutup_pada->isFuture()) {
            return;
        }

        foreach ($this->jawaban as $jawaban) {
            $jawaban->update([
                'poin' => $jawaban->jawaban === $this->jawaban_benar ? self::POIN_BENAR : 0,
            ]);
        }

        $this->update(['status' => 'dibuka']);
    }

    public static function bukaSemuaYangSudahDitutup(): void
    {
        self::where('status', 'disetujui')
            ->where('ditutup_pada', '<=', now())
            ->get()
            ->each->bukaJikaSudahDitutup();
    }

    public static function papanPoinBulanIni(int $batas = 5)
    {
        return User::where('role', 'member')
            ->withSum(['jawabanTantangan as total_poin' => fn ($q) => $q->where('created_at', '>=', now()->startOfMonth())], 'poin')
            ->orderByDesc('total_poin')
            ->orderBy('name')
            ->get()
            ->map(function ($user) {
                $user->total_poin = (int) $user->total_poin;
                return $user;
            })
            ->take($batas === 0 ? PHP_INT_MAX : $batas);
    }
}