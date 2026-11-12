<?php


namespace App\Http\Controllers;


class InformasiController extends Controller
{
    public function bantuan()
    {
        return view('publik.bantuan');
    }


    public function syarat()
    {
        return view('publik.syarat');
    }
}
