<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    protected $table = 'review';

    protected $fillable = [
        'analisis_id', 'mentor_id', 'status', 'penilaian', 'catatan',
        'direview_pada', 'ditugaskan_pada',
    ];

    protected function casts(): array
    {
        return [
            'direview_pada' => 'datetime',
            'ditugaskan_pada' => 'datetime',
        ];
    }

    public const PENILAIAN = ['benar' => 'Benar', 'koreksi' => 'Perlu koreksi', 'salah' => 'Salah baca'];

    public function analisis()
    {
        return $this->belongsTo(Analisis::class)->withTrashed();
    }

    public function mentor()
    {
        return $this->belongsTo(User::class, 'mentor_id');
    }



    public static function batasMenit(): int
    {
        return (int) config('lilin.batas_review_menit');
    }

    public function mulaiDihitung()
    {
        return $this->ditugaskan_pada ?? $this->created_at;
    }

    public function sisaMenit(): int
    {
        return max(0, self::batasMenit() - (int) $this->mulaiDihitung()->diffInMinutes(now()));
    }

    public function terlambat(): bool
    {
        return $this->status === 'menunggu' && $this->sisaMenit() === 0;
    }


    public function pindahkan(): ?User
    {
        $baru = User::mentorPalingLuang(array_filter([$this->mentor_id, $this->analisis?->user_id]));
        if (! $baru) {
            return null;
        }

        $diubah = self::where('id', $this->id)
            ->where('status', 'menunggu')
            ->where('mentor_id', $this->mentor_id)
            ->update([
                'mentor_id' => $baru->id,
                'ditugaskan_pada' => now(),
                'updated_at' => now(),
            ]);

        return $diubah ? $baru : null;
    }

    public static function pindahkanDariMentor(User $mentor): int
    {
        $jumlah = 0;

        foreach (self::with('analisis')->where('status', 'menunggu')->where('mentor_id', $mentor->id)->get() as $review) {
            $jumlah += $review->pindahkan() ? 1 : 0;
        }

        return $jumlah;
    }
}