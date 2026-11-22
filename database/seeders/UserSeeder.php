<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $akun = [
            ['Nadia', 'nadia@contoh.id', 'member', 'biasa', null],
            ['Fajar', 'fajar@contoh.id', 'member', 'premium', 30],
            ['Sari', 'sari@contoh.id', 'member', 'premium', 60],
            ['Bima', 'bima@contoh.id', 'member', 'biasa', null],
            ['Tari', 'tari@contoh.id', 'member', 'biasa', null],
            ['Dina', 'dina@contoh.id', 'mentor', 'biasa', null],
            ['Yoga', 'yoga@contoh.id', 'mentor', 'biasa', null],
            ['Laras', 'laras@contoh.id', 'mentor', 'biasa', null],
            ['Rahmat', 'rahmat@contoh.id', 'admin', 'biasa', null],
        ];

        foreach ($akun as [$nama, $email, $peran, $paket, $hariPremium]) {
            $user = new User();
            $user->name = $nama;
            $user->email = $email;
            $user->password = Hash::make('rahasia123');
            $user->role = $peran;
            $user->paket = $paket;
            $user->premium_sampai = $hariPremium ? today()->addDays($hariPremium) : null;
            $user->status = 'aktif';
            $user->save();
        }
    }
}