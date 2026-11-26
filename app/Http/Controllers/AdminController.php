<?php

namespace App\Http\Controllers;

use App\Models\CatatanAdmin;
use App\Models\Pembayaran;
use App\Models\Review;
use App\Models\Tantangan;
use App\Models\User;
use App\Support\Harga;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class AdminController extends Controller
{
    public function index()
    {
        $queryPembayaran = Pembayaran::where('status', 'menunggu');
        $queryTantangan = Tantangan::where('status', 'diajukan');
        $queryReview = Review::where('status', 'menunggu');

        return view('admin.index', [
            'title' => 'Pengelolaan',
            'pembayaran' => (clone $queryPembayaran)->with('user')->oldest()->take(25)->get(),
            'pembayaranTerbaru' => Pembayaran::with('user')->where('status', 'disetujui')->latest('diperiksa_pada')->take(10)->get(),
            'tantangan' => (clone $queryTantangan)->with('mentor')->oldest()->take(25)->get(),
            'reviewMenunggu' => (clone $queryReview)->with('analisis.user', 'mentor')->oldest()->take(25)->get(),
            'jumlah' => [
                'pembayaran' => $queryPembayaran->count(),
                'tantangan' => $queryTantangan->count(),
                'review' => $queryReview->count(),
            ],
            'mentor' => User::where('role', 'mentor')->withCount(['reviewDitugaskan as antrean' => fn ($q) => $q->where('status', 'menunggu')])->orderBy('name')->get(),
            'catatan' => CatatanAdmin::with('admin')->latest()->take(20)->get(),
        ]);
    }

    public function periksaPembayaran(Request $request, Pembayaran $pembayaran)
    {
        $request->validate(['aksi' => ['required', 'in:setujui,tolak']]);

        $sudah = 'Pembayaran sudah diperiksa atau member masih memiliki premium aktif. Periksa statusnya sebelum memproses kembali.';

        if ($request->input('aksi') === 'setujui') {
            if (! $pembayaran->setujui($request->user())) {
                return back()->withErrors(['pembayaran' => $sudah]);
            }
            $member = $pembayaran->user()->first();
            CatatanAdmin::catat('Setujui pembayaran', $member->name . ', ' . $pembayaran->durasi_bulan . ' bulan, ' . Harga::rupiah($pembayaran->nominal()));
            return back()->with('sukses', $member->name . ' sekarang member premium sampai ' . $member->premium_sampai->translatedFormat('j F Y') . '.');
        }

        if (! $pembayaran->tolak($request->user())) {
            return back()->withErrors(['pembayaran' => $sudah]);
        }
        CatatanAdmin::catat('Tolak pembayaran', $pembayaran->user->name . ', ' . $pembayaran->durasi_bulan . ' bulan');
        return back()->with('sukses', 'Pembayaran ' . $pembayaran->user->name . ' ditolak.');
    }

    public function cabutPembayaran(Request $request, Pembayaran $pembayaran)
    {
        if (! $pembayaran->cabut($request->user())) {
            return back()->withErrors(['pembayaran' => 'Pembayaran ini sudah tidak disetujui atau ada langganan yang lebih baru.']);
        }

        $member = $pembayaran->user()->first();
        CatatanAdmin::catat('Cabut premium', $member->name . ', ' . $pembayaran->durasi_bulan . ' bulan, ' . Harga::rupiah($pembayaran->nominal()));

        return back()->with('sukses', 'Premium ' . $member->name . ' dicabut sebanyak ' . $pembayaran->durasi_bulan . ' bulan. ' . ($member->isPremium() ? 'Masa berlakunya sekarang sampai ' . $member->premium_sampai->translatedFormat('j F Y') . '.' : 'Akunnya kembali ke paket biasa.'));
    }

    public function pindahkanReview(Review $review)
    {
        if ($review->status !== 'menunggu') {
            return back()->withErrors(['review' => 'Review ini sudah selesai atau dibatalkan.']);
        }

        $dari = $review->mentor?->name ?? 'mentor sebelumnya';
        $baru = $review->pindahkan();
        if (! $baru) {
            return back()->withErrors(['review' => 'Tidak ada mentor aktif lain yang bisa menerima review ini. Aktifkan mentor tambahan di Akun dan peran, atau minta member membatalkannya.']);
        }

        CatatanAdmin::catat('Pindahkan review', $review->analisis->judul() . ': ' . $dari . ' ke ' . $baru->name);

        return back()->with('sukses', 'Review dipindahkan ke ' . $baru->name . '. Member dapat melihat mentor yang baru di halaman hasil.');
    }

    public function buktiPembayaran(Pembayaran $pembayaran)
    {
        return Storage::disk('local')->response($pembayaran->bukti);
    }

    public function periksaTantangan(Request $request, Tantangan $tantangan)
    {
        $request->validate(['aksi' => ['required', 'in:setujui,tolak']]);

        if ($tantangan->status !== 'diajukan') {
            return back();
        }

        $tantangan->update(['status' => $request->input('aksi') === 'setujui' ? 'disetujui' : 'ditolak']);
        CatatanAdmin::catat($request->input('aksi') === 'setujui' ? 'Setujui tantangan' : 'Tolak tantangan', $tantangan->judul);

        return back()->with('sukses', 'Tantangan "' . $tantangan->judul . '" ' . ($request->input('aksi') === 'setujui' ? 'disetujui dan tayang.' : 'ditolak.'));
    }

    public function pengguna(Request $request)
    {
        $peran = $request->query('peran');

        return view('admin.pengguna', [
            'title' => 'Akun dan peran',
            'peran' => $peran,
            'daftar' => User::when(in_array($peran, ['member', 'mentor', 'admin'], true), fn ($q) => $q->where('role', $peran))
                ->orderBy('role')->orderBy('name')->paginate(20)->withQueryString(),
        ]);
    }

    public function ubahPengguna(Request $request, User $user)
    {
        $data = $request->validate([
            'role' => ['required', 'in:member,mentor,admin'],
            'status' => ['required', 'in:aktif,nonaktif'],
        ]);

        if ($user->id === $request->user()->id && ($data['role'] !== 'admin' || $data['status'] !== 'aktif')) {
            return back()->withErrors(['pengguna' => 'Anda tidak bisa menurunkan atau menonaktifkan akun sendiri.']);
        }

        $perubahan = $user->only(['role', 'status']);
        $user->update($data);

        $dipindah = 0;
        if ($perubahan['role'] === 'mentor' && ($data['role'] !== 'mentor' || $data['status'] !== 'aktif')) {
            $dipindah = Review::pindahkanDariMentor($user);
        }
        if ($perubahan != $data) CatatanAdmin::catat('Ubah akun', $user->email . ': ' . $perubahan['role'] . '/' . $perubahan['status'] . ' menjadi ' . $data['role'] . '/' . $data['status']);

        return back()->with('sukses', "Akun {$user->name} diperbarui." . ($dipindah ? " {$dipindah} review yang ia pegang dipindah ke mentor lain." : ''));
    }

    public function setelSandi(Request $request, User $user)
    {
        if ($user->id === $request->user()->id || $user->isAdmin()) {
            return back()->withErrors(['pengguna' => 'Sandi sementara hanya untuk akun member dan mentor. Admin mengganti sandinya sendiri lewat halaman Profil.']);
        }

        $sandi = Str::lower(Str::random(9)) . random_int(10, 99);

        $user->forceFill([
            'password' => $sandi,
            'sandi_sementara' => true,
            'remember_token' => Str::random(60),
        ])->save();

        DB::table('sessions')->where('user_id', $user->id)->delete();

        CatatanAdmin::catat('Setel sandi sementara', $user->email);

        return back()->with('sandiSementara', ['nama' => $user->name, 'email' => $user->email, 'sandi' => $sandi]);
    }
}
