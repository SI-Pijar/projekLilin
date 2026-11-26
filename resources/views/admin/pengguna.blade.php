@extends('layouts.app')

@section('isi')
<div class="kepala">
    <div>
        <h1>Akun dan peran</h1>
        <p class="pengantar">Angkat member yang lolos seleksi menjadi mentor, atau nonaktifkan akun yang bermasalah.</p>
    </div>
</div>

@if (session('sandiSementara'))
    <section class="pesan catatan tumpuk rapat" role="status">
        <h2>Sandi sementara untuk {{ session('sandiSementara')['nama'] }}</h2>
        <p class="sandi-sementara">{{ session('sandiSementara')['sandi'] }}</p>
        <p>Sampaikan lewat jalur yang aman. Sandi ini hanya tampil sekali. Saat masuk, {{ session('sandiSementara')['email'] }} diminta langsung menggantinya, dan semua sesi lamanya sudah diputus.</p>
    </section>
@endif

<nav aria-label="Saring peran" class="saring">
    @foreach (['' => 'Semua', 'member' => 'Member', 'mentor' => 'Mentor', 'admin' => 'Admin'] as $nilai => $label)
        <a href="{{ route('admin.pengguna', $nilai ? ['peran' => $nilai] : []) }}" @if (($peran ?? '') === $nilai) aria-current="true" @endif>{{ $label }}</a>
    @endforeach
</nav>

<section class="tumpuk">
    <div class="gulir tabel">
        <table>
            <thead><tr><th>Nama</th><th>Email</th><th>Paket</th><th>Peran dan status</th><th></th></tr></thead>
            <tbody>
            @foreach ($daftar as $u)
                <tr>
                    <td><b>{{ $u->name }}</b></td>
                    <td class="kecil">{{ $u->email }}</td>
                    <td class="kecil">
                        @if ($u->isMember())
                            {{ $u->isPremium() ? 'Premium s.d. ' . $u->premium_sampai->translatedFormat('j M Y') : 'Biasa' }}
                        @else
                            <span class="redup">—</span>
                        @endif
                    </td>
                    <td>
                        <form method="POST" action="{{ route('admin.pengguna.update', $u) }}" class="baris form-peran">
                            @csrf
                            @method('PUT')
                            <select id="role-{{ $u->id }}" name="role" aria-label="Peran {{ $u->name }}">
                                @foreach (['member' => 'Member', 'mentor' => 'Mentor', 'admin' => 'Admin'] as $nilai => $label)
                                    <option value="{{ $nilai }}" @selected($u->role === $nilai)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <select name="status" aria-label="Status {{ $u->name }}">
                                <option value="aktif" @selected($u->status === 'aktif')>Aktif</option>
                                <option value="nonaktif" @selected($u->status === 'nonaktif')>Nonaktif</option>
                            </select>
                            <button class="tombol kedua kecil" type="submit">Simpan</button>
                        </form>
                    </td>
                    <td>
                        @if ($u->role !== 'admin' && $u->id !== auth()->id())
                            <form method="POST" action="{{ route('admin.pengguna.sandi', $u) }}" onsubmit="return confirm('Setel sandi sementara untuk {{ e($u->name) }}? Sandi lamanya langsung tidak berlaku.')">
                                @csrf
                                @method('PUT')
                                <button class="tombol kedua kecil" type="submit">Setel sandi sementara</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    {{ $daftar->links('partials.paginasi') }}
</section>
@endsection
