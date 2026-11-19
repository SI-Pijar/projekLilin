<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function create()
    {
        return view('publik.masuk', [
            'title' => 'Masuk',
        ]);
    }

    public function store(Request $request)
    {
        $this->rapikanEmail($request);

        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::attempt($data, $request->boolean('ingat'))) {
            return back()->withErrors(['email' => 'Email atau kata sandi salah.'])->onlyInput('email');
        }

        if (Auth::user()->status !== 'aktif') {
            Auth::logout();
            return back()->withErrors(['email' => 'Akun Anda sedang dinonaktifkan admin.'])->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->intended(Auth::user()->halamanAwal());
    }

    public function daftar()
    {
        return view('publik.daftar', ['title' => 'Daftar']);
    }

    public function simpanDaftar(Request $request)
    {
        $this->rapikanEmail($request);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:60'],
            'email' => ['required', 'email', 'max:120', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
            'setuju' => ['accepted'],
        ], ['setuju.accepted' => 'Centang dulu persetujuan syarat dan disclaimer.']);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => 'member',
            'paket' => 'biasa',
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('analisis.create')->with('sukses', 'Akun dibuat. Selamat datang di Lilin!');
    }

    private function rapikanEmail(Request $request): void
    {
        if (is_string($request->input('email'))) {
            $request->merge(['email' => strtolower(trim($request->input('email')))]);
        }
    }

    public function destroy(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('beranda')->with('sukses', 'Anda sudah keluar.');
    }

}
