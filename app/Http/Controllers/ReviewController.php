<?php

namespace App\Http\Controllers;

use App\Models\Analisis;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReviewController extends Controller
{
    public function store(Request $request, Analisis $analisis)
    {
        $user = $request->user();
        abort_unless($analisis->user_id === $user->id, 403);

        if (! $user->isPremium()) {
            return redirect()->route('paket.index')->withErrors(['paket' => 'Review mentor khusus member premium.']);
        }
        if ($analisis->status !== 'selesai' || $analisis->h('status') === 'bukan_chart' || $analisis->review) {
            return back();
        }

        $mentor = User::mentorPalingLuang([$analisis->user_id]);
        if (! $mentor) {
            return back()->withErrors(['review' => 'Belum ada mentor aktif. Coba lagi nanti.']);
        }

        try {
            DB::transaction(function () use ($analisis, $mentor) {
                Review::create([
                    'analisis_id' => $analisis->id,
                    'mentor_id' => $mentor->id,
                    'status' => 'menunggu',
                    'ditugaskan_pada' => now(),
                ]);
                $analisis->update(['status_review' => 'menunggu']);
            });
        } catch (UniqueConstraintViolationException) {
            return back()->withErrors(['review' => 'Review untuk analisis ini sudah diminta.']);
        }

        return back()->with('sukses', "Permintaan review masuk ke antrean {$mentor->name}. Admin dapat memindahkannya bila mentor berhalangan.");
    }


    public function setuju(Request $request, Review $review)
    {
        $this->pastikanPemilik($request, $review);

        if ($review->analisis->status_review === 'direview') {
            $review->analisis->update(['status_review' => 'final']);
        }

        return back()->with('sukses', 'Analisis ini sekarang final.');
    }


    public function batal(Request $request, Review $review)
    {
        abort_unless($review->analisis->user_id === $request->user()->id, 403);

        $dibatalkan = DB::transaction(function () use ($review) {
            $kini = Review::whereKey($review->id)->lockForUpdate()->first();
            if (! $kini || ! $kini->terlambat()) {
                return false;
            }

            $kini->delete();
            Analisis::withTrashed()->whereKey($kini->analisis_id)->update(['status_review' => 'tidak_diminta']);

            return true;
        });

        if (! $dibatalkan) {
            return back()->withErrors(['review' => 'Review hanya bisa dibatalkan setelah melewati batas ' . Review::batasMenit() . ' menit dan selama belum dikerjakan mentor.']);
        }

        return redirect()->route('analisis.show', $review->analisis_id)
            ->with('sukses', 'Permintaan review dibatalkan. Anda bisa memintanya lagi kapan saja.');
    }

    private function pastikanPemilik(Request $request, Review $review): void
    {
        abort_unless($review->analisis->user_id === $request->user()->id, 403);
        abort_unless($review->status === 'selesai', 403, 'Review belum selesai.');
    }
}