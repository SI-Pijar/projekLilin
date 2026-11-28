<?php

namespace App\Http\Controllers;

use App\Models\JawabanTantangan;
use App\Models\Tantangan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TantanganController extends Controller
{
    public function index(Request $request)
    {
        Tantangan::bukaSemuaYangSudahDitutup();

        $aktif = Tantangan::where('status', 'disetujui')
            ->where('ditutup_pada', '>', now())
            ->orderBy('ditutup_pada')
            ->first();

        return view('member.tantangan', [
            'title' => 'Tantangan mingguan',
            'aktif' => $aktif,
            'jawabanSaya' => $aktif ? $aktif->jawaban()->where('user_id', $request->user()->id)->first() : null,
            'selesai' => Tantangan::with('mentor')->where('status', 'dibuka')->latest('ditutup_pada')->take(3)->get(),
            'jawabanLama' => JawabanTantangan::where('user_id', $request->user()->id)->get()->keyBy('tantangan_id'),
            'papan' => Tantangan::papanPoinBulanIni(0),
        ]);
    }

    public function jawab(Request $request, Tantangan $tantangan)
    {
        if (! $tantangan->masihBuka()) {
            return back()->withErrors(['jawaban' => 'Tantangan ini sudah ditutup.']);
        }

        $data = $request->validate([
            'jawaban' => ['required', 'in:naik,datar,turun'],
            'alasan' => ['nullable', 'string', 'max:200'],
        ], ['jawaban.required' => 'Pilih salah satu arah dulu.']);

        $sudah = $tantangan->jawaban()->where('user_id', $request->user()->id)->exists();
        if ($sudah) {
            return back()->withErrors(['jawaban' => 'Anda sudah menjawab tantangan ini.']);
        }

        JawabanTantangan::create([
            'tantangan_id' => $tantangan->id,
            'user_id' => $request->user()->id,
            'jawaban' => $data['jawaban'],
            'alasan' => $data['alasan'] ?? null,
        ]);

        return back()->with('sukses', 'Tebakan tersimpan. Lanjutan chart dibuka ' . $tantangan->ditutup_pada->translatedFormat('l, j F \p\u\k\u\l H.i') . '.');
    }

    public function gambar(Request $request, Tantangan $tantangan, string $jenis)
    {
        $user = $request->user();
        $boleh = $user->isAdmin()
            || $tantangan->mentor_id === $user->id
            || ($jenis === 'potong'
                ? in_array($tantangan->status, ['disetujui', 'dibuka'], true)
                : $tantangan->status === 'dibuka');

        abort_unless($boleh, 403, 'Gambar ini belum bisa dibuka.');

        $path = $jenis === 'potong' ? $tantangan->gambar_potong : $tantangan->gambar_lanjutan;

        return Storage::disk('local')->response($path);
    }
}