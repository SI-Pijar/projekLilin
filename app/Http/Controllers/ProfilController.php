<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class ProfilController extends Controller
{
    public function edit(Request $request)
    {
        return view('profil.edit', [
            'title' => 'Profil dan sandi',
            'user' => $request->user(),
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        if (is_string($request->input('email'))) {
            $request->merge(['email' => strtolower(trim($request->input('email')))]);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:60'],
            'email' => ['required', 'email', 'max:120', Rule::unique('users', 'email')->ignore($user->id)],
        ]);

        if ($data['email'] !== $user->email) {
            $request->validate(
                ['sandi_email' => ['required', 'current_password']],
                ['sandi_email.required' => 'Isi kata sandi sekarang untuk mengganti email.', 'sandi_email.current_password' => 'Kata sandi sekarang salah.']
            );
        }

        $user->update(['name' => $data['name'], 'email' => $data['email']]);

        return back()->with('sukses', 'Profil diperbarui.');
    }

    public function sandi(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', 'different:current_password', Password::min(8)->letters()->numbers()],
        ]);

        $user->forceFill([
            'password' => $data['password'],
            'sandi_sementara' => false,
            'remember_token' => Str::random(60),
        ])->save();

        $request->session()->regenerate();

        return back()->with('sukses', 'Kata sandi diganti. Perangkat lain yang memakai "ingat saya" perlu masuk ulang.');
    }
}
