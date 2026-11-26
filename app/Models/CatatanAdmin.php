<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CatatanAdmin extends Model
{
    protected $table = 'catatan_admin';

    protected $fillable = ['admin_id', 'tindakan', 'keterangan'];

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public static function catat(string $tindakan, ?string $keterangan = null): void
    {
        static::create([
            'admin_id' => auth()->id(),
            'tindakan' => $tindakan,
            'keterangan' => $keterangan ? mb_substr($keterangan, 0, 255) : null,
        ]);
    }
}
