<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'paket', 'premium_sampai', 'status'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    use HasFactory, Notifiable;

    public const KUOTA_HARIAN = 3;

    protected function casts(): array
    {
        return [
            'premium_sampai' => 'date',
            'password' => 'hashed',
            'sandi_sementara' => 'boolean',
        ];
    }

    public function analisis()
    {
        return $this->hasMany(Analisis::class);
    }

    public function reviewDitugaskan()
    {
        return $this->hasMany(Review::class, 'mentor_id');
    }

    public function tantanganDibuat()
    {
        return $this->hasMany(Tantangan::class, 'mentor_id');
    }

    public function jawabanTantangan()
    {
        return $this->hasMany(JawabanTantangan::class);
    }

    public function pembayaran()
    {
        return $this->hasMany(Pembayaran::class);
    }


    public function isMember(): bool
    {
        return $this->role === 'member';
    }

    public function isMentor(): bool
    {
        return $this->role === 'mentor';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isPremium(): bool
    {
        return $this->paket === 'premium'
            && $this->premium_sampai !== null
            && $this->premium_sampai->endOfDay()->isFuture();
    }

    public function analisisHariIni(): int
    {
        return $this->analisis()
            ->withTrashed()
            ->whereDate('created_at', today())
            ->where('status', '!=', 'gagal')
            ->count();
    }

    public function analisisBulanIni(): int
    {
        return $this->analisis()
            ->withTrashed()
            ->where('created_at', '>=', now()->startOfMonth())
            ->where('status', '!=', 'gagal')
            ->count();
    }

    public function batasKuota(): int
    {
        return $this->isPremium() ? (int) config('lilin.kuota_premium_bulanan') : self::KUOTA_HARIAN;
    }

    public function periodeKuota(): string
    {
        return $this->isPremium() ? 'bulan' : 'hari';
    }

    public function sisaKuota(): int
    {
        $terpakai = $this->isPremium() ? $this->analisisBulanIni() : $this->analisisHariIni();

        return max(0, $this->batasKuota() - $terpakai);
    }



    public function labelPeran(): string
    {
        return match ($this->role) {
            'mentor' => 'Mentor',
            'admin' => 'Admin',
            default => $this->isPremium() ? 'Member premium' : 'Member biasa',
        };
    }

    public function inisial(): string
    {
        return mb_strtoupper(mb_substr($this->name, 0, 1));
    }

    public function halamanAwal(): string
    {
        return match ($this->role) {
            'mentor' => route('mentor.index'),
            'admin' => route('admin.index'),
            default => route('analisis.create'),
        };
    }


    public static function mentorPalingLuang(array $kecuali = []): ?self
    {
        return self::where('role', 'mentor')
            ->where('status', 'aktif')
            ->whereNotIn('id', $kecuali)
            ->withCount(['reviewDitugaskan as antrean' => fn ($q) => $q->where('status', 'menunggu')])
            ->orderBy('antrean')
            ->orderBy('id')
            ->first();
    }
}
