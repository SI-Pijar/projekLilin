@extends('layouts.app')

@section('isi')
<div class="kepala">
    <div>
        <h1>Profil dan sandi</h1>
        <p class="pengantar">Ubah nama, email, atau kata sandi Anda.</p>
    </div>
    <p class="kecil">
        {{ $user->labelPeran() }}
        @if ($user->isMember() && $user->isPremium())
            · premium sampai {{ $user->premium_sampai->translatedFormat('j F Y') }}
        @endif
    </p>
</div>

<div class="dua rata">
    <form method="POST" action="{{ route('profil.update') }}" class="kartu lapang tumpuk longgar">
        @csrf
        @method('PUT')
        <h2>Data diri</h2>
        <div class="isian">
            <label for="name">Nama panggilan</label>
            <input id="name" name="name" value="{{ old('name', $user->name) }}" maxlength="60" autocomplete="nickname" required>
        </div>
        <div class="isian">
            <label for="email">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" maxlength="120" autocomplete="email" required>
        </div>
        <div class="isian">
            <label for="sandi_email">Kata sandi sekarang</label>
            <input id="sandi_email" name="sandi_email" type="password" autocomplete="current-password" aria-describedby="bantu-sandi-email">
            <span class="bantu" id="bantu-sandi-email">Isi hanya jika Anda mengganti email.</span>
            @error('sandi_email') <span class="salah">{{ $message }}</span> @enderror
        </div>
        <p><button class="tombol" type="submit">Simpan profil</button></p>
    </form>

    <form method="POST" action="{{ route('profil.sandi') }}" class="kartu lapang tumpuk longgar">
        @csrf
        @method('PUT')
        <h2>Ganti kata sandi</h2>
        <div class="isian">
            <label for="current_password">Kata sandi sekarang</label>
            <input id="current_password" name="current_password" type="password" autocomplete="current-password" required>
        </div>
        <div class="isian">
            <label for="password">Kata sandi baru</label>
            <input id="password" name="password" type="password" autocomplete="new-password" minlength="8" required aria-describedby="bantu-sandi-baru">
            <span class="bantu" id="bantu-sandi-baru">Minimal 8 karakter.</span>
        </div>
        <div class="isian">
            <label for="password_confirmation">Ulangi kata sandi baru</label>
            <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required>
        </div>
        <p><button class="tombol" type="submit">Ganti kata sandi</button></p>
    </form>
</div>
@endsection