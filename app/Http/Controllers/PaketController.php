<?php

namespace App\Http\Controllers;

use App\Models\Pembayaran;
use App\Support\Bank;
use App\Support\Harga;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PaketController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $menunggu = $user->pembayaran()->where('status', 'menunggu')->latest()->first();

        return view('member.paket', [
            'title' => 'Paket saya',
            'premium' => $user->isPremium(),
            'menunggu' => $menunggu,
            'simulasi' => $menunggu ? $menunggu->simulasi : config('lilin.pembayaran_simulasi'),
            'riwayat' => $user->pembayaran()->latest()->take(5)->get(),
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        if ($user->isPremium()) {
            return back()->withErrors(['bukti' => 'Premium Anda masih aktif. Pengajuan baru dibuka setelah masa premium berakhir.']);
        }
        if (! config('lilin.pembayaran_simulasi') && ! Harga::rekeningSiap()) {
            return back()->withErrors(['bukti' => 'Pembayaran belum dibuka. Rekening tujuan belum diatur.']);
        }
        if ($user->pembayaran()->where('status', 'menunggu')->exists()) {
            return back()->withErrors(['bukti' => 'Masih ada pembayaran yang menunggu diperiksa admin.']);
        }

        $data = $request->validate([
            'durasi_bulan' => ['required', 'in:1,3'],
            'bank_pengirim' => ['required', 'in:' . implode(',', array_keys(Bank::DAFTAR))],
            'rekening_pengirim' => ['required', 'string', 'max:30'],
            'nama_pengirim' => ['required', 'string', 'max:60'],
            'bukti' => ['required', 'image', 'mimes:png,jpg,jpeg', 'max:2048'],
        ], [
            'bukti.required' => 'Unggah foto bukti transfer dulu.',
            'bank_pengirim.required' => 'Pilih bank asal transfer Anda.',
            'bank_pengirim.in' => 'Pilih bank dari daftar yang tersedia.',
            'rekening_pengirim.required' => 'Isi nomor rekening Anda yang dipakai untuk transfer.',
            'nama_pengirim.required' => 'Isi nama pemilik rekening sesuai buku tabungan atau aplikasi bank.',
        ]);

        $nomor = Bank::bersihkan($data['rekening_pengirim']);
        if (! Bank::nomorSesuai($data['bank_pengirim'], $nomor)) {
            return back()->withInput()->withErrors(['rekening_pengirim' => 'Nomor rekening ' . Bank::nama($data['bank_pengirim']) . ' terdiri dari ' . Bank::petunjuk($data['bank_pengirim']) . ' (hanya angka).']);
        }

        $path = $request->file('bukti')->store('bukti', 'local');

        try {
            $dibuat = DB::transaction(function () use ($user, $data, $nomor, $path) {
                $kini = \App\Models\User::whereKey($user->id)->lockForUpdate()->firstOrFail();
                if ($kini->isPremium() || $kini->pembayaran()->where('status', 'menunggu')->exists()) {
                    return false;
                }
                Pembayaran::create([
                'user_id' => $user->id,
                'durasi_bulan' => (int) $data['durasi_bulan'],
                'jumlah' => Harga::paket((int) $data['durasi_bulan']),
                'bank_pengirim' => $data['bank_pengirim'],
                'rekening_pengirim' => $nomor,
                'nama_pengirim' => trim($data['nama_pengirim']),
                'bukti' => $path,
                'status' => 'menunggu',
                'simulasi' => (bool) config('lilin.pembayaran_simulasi'),
                ]);
                return true;
            });
        } catch (UniqueConstraintViolationException) {
            Storage::disk('local')->delete($path);

            return back()->withErrors(['bukti' => 'Masih ada pembayaran yang menunggu diperiksa admin.']);
        }

        if (! $dibuat) {
            Storage::disk('local')->delete($path);
            return back()->withErrors(['bukti' => 'Paket atau pengajuan Anda sudah berubah. Muat ulang halaman untuk melihat status terbaru.']);
        }

        return back()->with('sukses', 'Bukti terkirim. Admin akan memeriksa dan mengaktifkan premium Anda.');
    }

    public function update(Request $request, Pembayaran $pembayaran)
    {
        abort_unless($pembayaran->user_id === $request->user()->id, 403);
        if ($pembayaran->status !== 'menunggu') {
            return back()->withErrors(['bukti' => 'Pembayaran yang sudah diperiksa tidak bisa diubah.']);
        }

        $data = $request->validate([
            'durasi_bulan' => ['required', 'in:1,3'],
            'bank_pengirim' => ['required', 'in:' . implode(',', array_keys(Bank::DAFTAR))],
            'rekening_pengirim' => ['required', 'string', 'max:30'],
            'nama_pengirim' => ['required', 'string', 'max:60'],
            'bukti' => ['nullable', 'image', 'mimes:png,jpg,jpeg', 'max:2048'],
        ]);
        $nomor = Bank::bersihkan($data['rekening_pengirim']);
        if (! Bank::nomorSesuai($data['bank_pengirim'], $nomor)) {
            return back()->withInput()->withErrors(['rekening_pengirim' => 'Nomor rekening tidak sesuai dengan bank yang dipilih.']);
        }

        $baru = $request->hasFile('bukti') ? $request->file('bukti')->store('bukti', 'local') : null;
        $lama = null;
        $diubah = DB::transaction(function () use ($pembayaran, $data, $nomor, $baru, &$lama) {
            $kini = Pembayaran::whereKey($pembayaran->id)->lockForUpdate()->first();
            if (! $kini || $kini->status !== 'menunggu') {
                return false;
            }
            $lama = $kini->bukti;
            $kini->update([
                'durasi_bulan' => (int) $data['durasi_bulan'],
                'jumlah' => Harga::paket((int) $data['durasi_bulan']),
                'bank_pengirim' => $data['bank_pengirim'],
                'rekening_pengirim' => $nomor,
                'nama_pengirim' => trim($data['nama_pengirim']),
                'bukti' => $baru ?? $kini->bukti,
            ]);
            return true;
        });
        if (! $diubah) {
            if ($baru) Storage::disk('local')->delete($baru);
            return back()->withErrors(['bukti' => 'Pembayaran sudah diperiksa atau dibatalkan. Perubahan tidak disimpan.']);
        }
        if ($baru && $lama) Storage::disk('local')->delete($lama);
        return back()->with('sukses', 'Pengajuan pembayaran diperbarui. Admin akan memeriksa data yang terbaru.');
    }

    public function destroy(Request $request, Pembayaran $pembayaran)
    {
        abort_unless($pembayaran->user_id === $request->user()->id, 403);

        $bukti = DB::transaction(function () use ($pembayaran) {
            $kini = Pembayaran::whereKey($pembayaran->id)->lockForUpdate()->first();
            if (! $kini || $kini->status !== 'menunggu') {
                return null;
            }
            $path = $kini->bukti;
            $kini->delete();
            return $path;
        });
        if ($bukti === null) {
            return back()->withErrors(['bukti' => 'Pembayaran yang sudah diperiksa admin tidak bisa dibatalkan.']);
        }

        Storage::disk('local')->delete($bukti);

        return back()->with('sukses', 'Pengajuan pembayaran dibatalkan. Silakan kirim ulang bukti yang benar.');
    }
}
