<?php

namespace App\Http\Controllers;

class InformasiController extends Controller
{
    public function bantuan()
    {
        return view('publik.bantuan', ['title' => 'Bantuan']);
    }

    public function syarat()
    {
        return view('publik.syarat', ['title' => 'Syarat dan disclaimer']);
    }
}
