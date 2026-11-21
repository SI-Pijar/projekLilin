<?php

namespace App\Http\Controllers;

use App\Models\Analisis;
use App\Models\User;

class BerandaController extends Controller
{
    public function index()
    {
        return view('publik.beranda', [
            'title' => 'Baca Chart dari Foto',
            'jumlahAnalisis' => Analisis::where('status', 'selesai')->count(),
            'jumlahMentor' => User::where('role', 'mentor')->where('status', 'aktif')->count(),
            'kuota' => User::KUOTA_HARIAN,
        ]);
    }
}
