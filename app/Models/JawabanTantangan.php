<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JawabanTantangan extends Model
{
    protected $table = 'jawaban_tantangan';

    protected $fillable = ['tantangan_id', 'user_id', 'jawaban', 'alasan', 'poin'];

    public function tantangan()
    {
        return $this->belongsTo(Tantangan::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}