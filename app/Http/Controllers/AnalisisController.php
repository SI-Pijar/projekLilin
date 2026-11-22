<?php

namespace App\Http\Controllers;

use App\Models\Analisis;
use App\Models\Review;
use App\Models\User;
use App\Services\PembacaChart;
use App\Support\Harga;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class AnalisisController extends Controller
{
    public function create(Request $request)
    {
        $user = $request->user();

        return view('member.analisis', [
            'title' => 'Analisis baru',
            'sisaKuota' => $user->sisaKuota(),
            'premium' => $user->isPremium(),
            'periode' => $user->periodeKuota(),
            'terakhir' => $user->analisis()->latest()->take(5)->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'gambar' => ['required', 'image', 'mimes:png,jpg,jpeg,webp', 'max:5120'],
            'kode_saham' => ['nullable', 'alpha', 'max:4'],
            'rentang' => ['required', 'in:1H,1D,1W'],
            'tebak_tren' => ['required', 'in:naik,turun,datar'],
            'tebak_support' => ['nullable', 'string', 'max:14', $this->angkaHarga()],
            'tebak_resistance' => ['nullable', 'string', 'max:14', $this->angkaHarga()],
        ], [
            'tebak_tren.required' => 'Tebak dulu tren chart ini sebelum melihat jawaban AI.',
            'tebak_tren.in' => 'Pilih salah satu: naik, turun, atau datar.',
        ]);

        $analisis = DB::transaction(function () use ($request, $data) {
            $user = User::whereKey($request->user()->id)->lockForUpdate()->first();

            if ($user->sisaKuota() === 0) {
                return $user;
            }

            return Analisis::create([
                'user_id' => $user->id,
                'gambar' => $request->file('gambar')->store('chart', 'public'),
                'kode_saham' => isset($data['kode_saham']) ? strtoupper($data['kode_saham']) : null,
                'rentang' => $data['rentang'],
                'tebakan' => [
                    'tren' => $data['tebak_tren'],
                    'support' => Harga::urai($data['tebak_support'] ?? null),
                    'resistance' => Harga::urai($data['tebak_resistance'] ?? null),
                ],
                'status' => 'diproses',
            ]);
        });

        if ($analisis instanceof User) {
            return back()->withErrors(['gambar' => $analisis->isPremium()
                ? 'Kuota ' . $analisis->batasKuota() . ' analisis bulan ini habis. Kuota kembali penuh pada tanggal 1 bulan depan.'
                : 'Kuota hari ini habis. Kuota kembali penuh besok, atau upgrade ke premium.']);
        }

        app(PembacaChart::class)->baca($analisis);

        return redirect()->route('analisis.show', $analisis);
    }

    public function ulang(Request $request, Analisis $analisis)
    {
        $this->pastikanPemilik($request, $analisis);

        $hasil = DB::transaction(function () use ($analisis, $request) {
            $kini = Analisis::whereKey($analisis->id)->lockForUpdate()->first();
            $user = User::whereKey($request->user()->id)->lockForUpdate()->first();

            if (! $kini || ! $kini->bisaDiulang()) {
                return 'tidak';
            }

            if ($kini->status === 'gagal' && $user->sisaKuota() === 0) {
                return 'kuota';
            }

            $kini->forceFill(['status' => 'diproses', 'pesan_galat' => null, 'created_at' => now()])->save();

            return 'jalan';
        });

        if ($hasil === 'tidak') {
            return back();
        }
        if ($hasil === 'kuota') {
            return back()->withErrors(['ulang' => $request->user()->isPremium()
                ? 'Kuota analisis bulan ini habis.'
                : 'Kuota hari ini habis.']);
        }

        $analisis->refresh();
        app(PembacaChart::class)->baca($analisis);

        return redirect()->route('analisis.show', $analisis);
    }

    public function update(Request $request, Analisis $analisis)
    {
        $this->pastikanPemilik($request, $analisis);

        $data = $request->validate([
            'kode_saham' => ['nullable', 'alpha', 'max:4'],
            'rentang' => ['required', 'in:1H,1D,1W'],
            'catatan' => ['nullable', 'string', 'max:2000'],
        ]);

        $analisis->update([
            'kode_saham' => isset($data['kode_saham']) ? strtoupper($data['kode_saham']) : null,
            'rentang' => $data['rentang'],
            'catatan' => $data['catatan'] ?? null,
        ]);

        return back()->with('sukses', 'Catatan dan data chart tersimpan.');
    }

    public function destroy(Request $request, Analisis $analisis)
    {
        $this->pastikanPemilik($request, $analisis);

        if (! $analisis->bolehDihapus()) {
            return back()->withErrors(['hapus' => 'Analisis yang sedang diperiksa mentor belum bisa dihapus.']);
        }

        Storage::disk('public')->delete($analisis->gambar);
        $analisis->delete();

        return redirect()->route('analisis.index')->with('sukses', 'Analisis dan screenshot-nya sudah dihapus.');
    }

    public function show(Request $request, Analisis $analisis)
    {
        $this->pastikanPemilik($request, $analisis);
        $analisis->load('review.mentor');

        return view('member.hasil', [
            'title' => $analisis->judul(),
            'analisis' => $analisis,
            'review' => $analisis->review,
            'premium' => $request->user()->isPremium(),
        ]);
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $query = $user->analisis()->with('review')->latest();

        if (! $user->isPremium()) {
            $query->where('created_at', '>=', now()->subDays(7));
        }

        $kodeSaham = $this->analisisBacaan($user)
            ->selectRaw('kode_saham, count(*) as jumlah')
            ->groupBy('kode_saham')
            ->orderByDesc('jumlah')
            ->orderBy('kode_saham')
            ->get();

        return view('member.riwayat', [
            'title' => 'Riwayat dan review',
            'daftar' => $query->paginate(10),
            'premium' => $user->isPremium(),
            'kodeSaham' => $kodeSaham,
        ]);
    }

    public function saham(Request $request, string $kode)
    {
        $user = $request->user();
        $kode = strtoupper($kode);

        $perRentang = $this->analisisBacaan($user)
            ->where('kode_saham', $kode)
            ->reorder()
            ->selectRaw('rentang, count(*) as jumlah')
            ->groupBy('rentang')
            ->pluck('jumlah', 'rentang');
        abort_if($perRentang->isEmpty(), 404, 'Belum ada analisis yang selesai untuk kode ' . $kode . '.');

        $rentang = $request->query('rentang');
        if (! $perRentang->has($rentang)) {
            $rentang = $perRentang->sortDesc()->keys()->first();
        }

        $seri = $this->analisisBacaan($user)
            ->where('kode_saham', $kode)
            ->where('rentang', $rentang)
            ->with('review')
            ->latest()
            ->take(150)
            ->get()
            ->reverse()
            ->values();

        return view('member.saham', [
            'title' => 'Riwayat ' . $kode,
            'kode' => $kode,
            'rentang' => $rentang,
            'perRentang' => $perRentang,
            'seri' => $seri,
            'support' => $this->pergeseran($seri, 'support.harga'),
            'resistance' => $this->pergeseran($seri, 'resistance.harga'),
            'tren' => $this->pergeseranTren($seri),
            'premium' => $user->isPremium(),
        ]);
    }

    private function analisisBacaan($user)
    {
        return $user->analisis()
            ->where('status', 'selesai')
            ->whereNotNull('kode_saham')
            ->whereRaw("coalesce(hasil->>'status', 'ok') <> 'bukan_chart'")
            ->when(! $user->isPremium(), fn ($q) => $q->where('created_at', '>=', now()->subDays(7)));
    }

    private function pergeseran($seri, string $kunci): array
    {
        $nilai = $seri->map(fn ($a) => $a->h($kunci))->filter(fn ($v) => is_numeric($v) && $v > 0)->values();

        if ($nilai->count() < 2) {
            return ['cukup' => false];
        }

        $awal = (float) $nilai->first();
        $kini = (float) $nilai->last();

        return [
            'cukup' => true,
            'awal' => $awal,
            'kini' => $kini,
            'persen' => ($kini - $awal) / $awal * 100,
        ];
    }

    private function pergeseranTren($seri): array
    {
        $daftar = $seri->map(fn ($a) => $a->h('tren'))->filter()->values();
        $berubah = 0;
        for ($i = 1; $i < $daftar->count(); $i++) {
            if ($daftar[$i] !== $daftar[$i - 1]) {
                $berubah++;
            }
        }

        return [
            'cukup' => $daftar->count() >= 2,
            'awal' => $daftar->first(),
            'kini' => $daftar->last(),
            'berubah' => $berubah,
        ];
    }

    private function angkaHarga(): \Closure
    {
        return function (string $atribut, mixed $nilai, \Closure $gagal) {
            $angka = Harga::urai($nilai);
            if ($angka === null || $angka <= 0) {
                $gagal('Tulis angka harga saja, misalnya 4790.');
            }
        };
    }

    private function pastikanPemilik(Request $request, Analisis $analisis): void
    {
        abort_unless(Gate::forUser($request->user())->allows('milik-analisis', $analisis), 403, 'Analisis ini milik member lain.');
    }
}
