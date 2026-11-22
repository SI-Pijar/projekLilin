<?php

use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Artisan;



Artisan::command('lilin:buat-admin {email : Email admin} {--nama= : Nama admin (untuk akun baru)}', function () {
    $email = mb_strtolower(trim($this->argument('email')));

    if (Validator::make(['email' => $email], ['email' => 'required|email:rfc'])->fails()) {
        $this->error('Format email tidak valid.');

        return 1;
    }

    $ada = User::where('email', $email)->first();
    if ($ada) {
        $ada->update(['role' => 'admin', 'status' => 'aktif']);
        $this->info("Akun {$email} sekarang admin. Sandinya tidak diubah.");

        return 0;
    }

    $sandi = (string) $this->secret('Kata sandi admin (minimal 8 karakter)');
    if (mb_strlen($sandi) < 8 || ! preg_match('/[A-Za-z]/', $sandi) || ! preg_match('/\d/', $sandi)) {
        $this->error('Kata sandi minimal 8 karakter dan memuat huruf serta angka. Akun tidak dibuat.');

        return 1;
    }

    $user = new User();
    $user->name = $this->option('nama') ?: 'Admin';
    $user->email = $email;
    $user->password = $sandi;
    $user->role = 'admin';
    $user->paket = 'biasa';
    $user->status = 'aktif';
    $user->save();

    $this->info("Admin {$email} dibuat. Silakan masuk lewat halaman /masuk.");

    return 0;
})->purpose('Buat akun admin pertama (atau jadikan akun yang ada sebagai admin)');
