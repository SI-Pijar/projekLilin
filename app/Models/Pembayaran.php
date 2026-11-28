<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Pembayaran extends Model
{
    protected $table = 'pembayaran';

    protected $fillable = ['user_id', 'durasi_bulan', 'jumlah', 'bank_pengirim', 'rekening_pengirim', 'nama_pengirim', 'bukti', 'status', 'admin_id', 'diperiksa_pada', 'simulasi'];

    protected function casts(): array
    {
        return [
            'simulasi' => 'boolean',
            'diperiksa_pada' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function setujui(User $admin): bool
    {
        return DB::transaction(function () use ($admin) {
            $kini = self::whereKey($this->id)->lockForUpdate()->first();
            if (! $kini || $kini->status !== 'menunggu') {
                return false;
            }

            $member = User::whereKey($kini->user_id)->lockForUpdate()->first();
            if ($member->isPremium()) {
                return false;
            }
            $mulai = today();

            $member->update([
                'paket' => 'premium',
                'premium_sampai' => $mulai->addMonthsNoOverflow($kini->durasi_bulan),
            ]);
            $kini->update(['status' => 'disetujui', 'admin_id' => $admin->id, 'diperiksa_pada' => now()]);

            $this->setRawAttributes($kini->getAttributes(), true);
            $this->unsetRelation('user');

            return true;
        });
    }

    public function cabut(User $admin): bool
    {
        return DB::transaction(function () use ($admin) {
            $kini = self::whereKey($this->id)->lockForUpdate()->first();
            if (! $kini || $kini->status !== 'disetujui') {
                return false;
            }

            $member = User::whereKey($kini->user_id)->lockForUpdate()->first();
            $adaLanggananLebihBaru = self::where('user_id', $kini->user_id)
                ->where('id', '>', $kini->id)
                ->whereIn('status', ['disetujui', 'dicabut'])
                ->exists();
            if ($adaLanggananLebihBaru) {
                return false;
            }
            $baru = $member->premium_sampai?->copy()->subMonthsNoOverflow($kini->durasi_bulan);

            $member->update($baru && $baru->greaterThan(today())
                ? ['premium_sampai' => $baru]
                : ['paket' => 'biasa', 'premium_sampai' => null]);
            $kini->update(['status' => 'dicabut', 'admin_id' => $admin->id, 'diperiksa_pada' => now()]);

            $this->setRawAttributes($kini->getAttributes(), true);
            $this->unsetRelation('user');

            return true;
        });
    }

    public function tolak(User $admin): bool
    {
        $diubah = self::whereKey($this->id)->where('status', 'menunggu')
            ->update(['status' => 'ditolak', 'admin_id' => $admin->id, 'diperiksa_pada' => now(), 'updated_at' => now()]);
        $this->refresh();

        return $diubah > 0;
    }

    public function nominal(): int
    {
        return (int) ($this->jumlah ?? \App\Support\Harga::paket((int) $this->durasi_bulan));
    }
}
