<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\Tantangan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class MentorController extends Controller
{
    public function index(Request $request)
    {
        $mentor = $request->user();

        $antrean = Review::with('analisis.user')
            ->where('mentor_id', $mentor->id)
            ->where('status', 'menunggu')
            ->oldest()
            ->get();

        $dipilih = $antrean->firstWhere('id', (int) $request->query('review')) ?? $antrean->first();

        return view('mentor.index', [
            'title' => 'Antrean review',
            'antrean' => $antrean,
            'dipilih' => $dipilih,
            'selesaiBulanIni' => $mentor->reviewDitugaskan()
                ->where('status', 'selesai')
                ->where('direview_pada', '>=', now()->startOfMonth())
                ->count(),
        ]);
    }

    public function kirimReview(Request $request, Review $review)
    {
        abort_unless($review->mentor_id === $request->user()->id, 403, 'Review ini bukan untuk Anda.');

        if ($review->status !== 'menunggu') {
            return redirect()->route('mentor.index');
        }

        $data = $request->validate([
            'penilaian' => ['required', 'in:benar,koreksi,salah'],
            'catatan' => ['required_unless:penilaian,benar', 'nullable', 'string', 'max:1000'],
        ], ['catatan.required_unless' => 'Catatan wajib diisi jika memilih Perlu koreksi atau Salah baca.']);

        $terkirim = DB::transaction(function () use ($review, $data, $request) {
            $kini = Review::whereKey($review->id)->lockForUpdate()->first();
            if (! $kini || $kini->status !== 'menunggu' || $kini->mentor_id !== $request->user()->id) {
                return false;
            }

            $kini->update([
                'penilaian' => $data['penilaian'],
                'catatan' => $data['catatan'] ?? null,
                'status' => 'selesai',
                'direview_pada' => now(),
            ]);
            $kini->analisis->update(['status_review' => 'direview']);

            return true;
        });

        if (! $terkirim) {
            return redirect()->route('mentor.index')->withErrors(['review' => 'Review ini sudah dikirim atau dipindahkan, jadi tidak diubah.']);
        }

        return redirect()->route('mentor.index')
            ->with('sukses', 'Review ' . $review->analisis->judul() . ' terkirim. Member sudah bisa melihatnya.');
    }

    public function tantangan(Request $request)
    {
        return view('mentor.tantangan', [
            'title' => 'Tantangan buatan saya',
            'daftar' => $request->user()->tantanganDibuat()->withCount('jawaban')->latest()->get(),
        ]);
    }

    public function simpanTantangan(Request $request)
    {
        $data = $request->validate([
            'judul' => ['required', 'string', 'max:120'],
            'gambar_potong' => ['required', 'image', 'mimes:png,jpg,jpeg,webp', 'max:5120'],
            'gambar_lanjutan' => ['required', 'image', 'mimes:png,jpg,jpeg,webp', 'max:5120'],
            'jawaban_benar' => ['required', 'in:naik,datar,turun'],
            'pembahasan' => ['nullable', 'string', 'max:1000'],
            'ditutup_pada' => ['required', 'date', 'after:now'],
        ], ['ditutup_pada.after' => 'Waktu tutup harus setelah sekarang.']);

        Tantangan::create([
            'mentor_id' => $request->user()->id,
            'judul' => $data['judul'],
            'gambar_potong' => $request->file('gambar_potong')->store('tantangan', 'local'),
            'gambar_lanjutan' => $request->file('gambar_lanjutan')->store('tantangan', 'local'),
            'jawaban_benar' => $data['jawaban_benar'],
            'pembahasan' => $data['pembahasan'] ?? null,
            'ditutup_pada' => $data['ditutup_pada'],
            'status' => 'diajukan',
        ]);

        return back()->with('sukses', 'Tantangan diajukan. Tantangan tayang setelah disetujui admin.');
    }

    public function editTantangan(Request $request, Tantangan $tantangan)
    {
        $this->pastikanBisaDiubah($request, $tantangan);

        return view('mentor.tantangan-edit', [
            'title' => 'Edit tantangan',
            'tantangan' => $tantangan,
        ]);
    }

    public function updateTantangan(Request $request, Tantangan $tantangan)
    {
        $this->pastikanBisaDiubah($request, $tantangan);

        $data = $request->validate([
            'judul' => ['required', 'string', 'max:120'],
            'gambar_potong' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:5120'],
            'gambar_lanjutan' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:5120'],
            'jawaban_benar' => ['required', 'in:naik,datar,turun'],
            'pembahasan' => ['nullable', 'string', 'max:1000'],
            'ditutup_pada' => ['required', 'date', 'after:now'],
        ], ['ditutup_pada.after' => 'Waktu tutup harus setelah sekarang.']);

        foreach (['gambar_potong', 'gambar_lanjutan'] as $kolom) {
            if ($request->hasFile($kolom)) {
                Storage::disk('local')->delete($tantangan->$kolom);
                $data[$kolom] = $request->file($kolom)->store('tantangan', 'local');
            } else {
                unset($data[$kolom]);
            }
        }

        $diajukanUlang = $tantangan->status === 'ditolak';
        $tantangan->update($data + ['status' => 'diajukan']);

        return redirect()->route('mentor.tantangan')
            ->with('sukses', $diajukanUlang ? 'Tantangan diperbaiki dan diajukan ulang ke admin.' : 'Perubahan tantangan tersimpan.');
    }

    public function hapusTantangan(Request $request, Tantangan $tantangan)
    {
        $this->pastikanBisaDiubah($request, $tantangan);

        Storage::disk('local')->delete([$tantangan->gambar_potong, $tantangan->gambar_lanjutan]);
        $tantangan->delete();

        return redirect()->route('mentor.tantangan')->with('sukses', 'Tantangan dihapus.');
    }

    private function pastikanBisaDiubah(Request $request, Tantangan $tantangan): void
    {
        abort_unless($tantangan->mentor_id === $request->user()->id, 403, 'Ini tantangan mentor lain.');
        abort_unless($tantangan->bisaDiubah(), 403, 'Tantangan yang sudah tayang tidak bisa diubah atau dihapus.');
    }
}