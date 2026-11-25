@extends('layouts.app')
@php
    use App\Support\Harga;
    $user = auth()->user();
    $rekeningSiap = Harga::rekeningSiap();
@endphp
@section('isi')
    <div class="kepala">
        <div>
            <h1>Paket saya</h1>
            <p class="pengantar">{{ $premium ? 'Paket premium Anda sedang aktif. Berikut status dan riwayat pembayaran Anda.' : ($simulasi ? 'Pilih durasi, isi data pengirim, lalu unggah bukti pembayaran. Premium aktif setelah admin memeriksa pengajuan Anda.' : 'Transfer ke rekening di bawah, unggah buktinya, lalu admin memeriksa. Paket premium aktif setelah disetujui.') }}</p>
        </div>
    </div>
    @if ($simulasi)
        <p class="pesan catatan"><b>Pembayaran sedang dalam mode uji coba.</b> Tidak ada uang yang dipindahkan. Gunakan nomor rekening dan gambar bukti contoh, jangan data perbankan pribadi.</p>
    @endif
    <div class="dua">
        @if (! $premium || $menunggu)
            <form method="POST" action="{{ $menunggu ? route('paket.update', $menunggu) : route('paket.store') }}" enctype="multipart/form-data" class="kartu lapang tumpuk longgar">
                @csrf
                @if ($menunggu)
                    @method('PUT')
                    <div class="tumpuk rapat">
                        <h2>Perbaiki pengajuan pembayaran</h2>
                        <p class="bantu">Pengajuan ini masih menunggu admin. Perbarui data yang keliru; bukti lama tetap dipakai bila Anda tidak mengunggah penggantinya.</p>
                    </div>
                @else
                    <ol class="alur" aria-label="Langkah pembayaran">
                        <li>1. Pilih durasi</li>
                        <li>2. {{ $simulasi ? 'Isi data pengirim' : 'Transfer' }}</li>
                        <li>3. Unggah bukti</li>
                        <li>4. Admin memeriksa</li>
                    </ol>
                @endif
                <fieldset class="tumpuk rapat">
                    <legend>Durasi langganan</legend>
                    <label class="opsi"><input type="radio" name="durasi_bulan" value="1" @checked(old('durasi_bulan', $menunggu?->durasi_bulan ?? '1') == '1')><span>1 bulan</span><b class="angka">{{ Harga::rupiah(Harga::paket(1)) }}</b></label>
                    <label class="opsi"><input type="radio" name="durasi_bulan" value="3" @checked(old('durasi_bulan', $menunggu?->durasi_bulan) == '3')><span>3 bulan</span><b class="angka">{{ Harga::rupiah(Harga::paket(3)) }}</b></label>
                </fieldset>
                @if ($simulasi)
                    <p class="bantu">Selama mode uji coba, tidak ada rekening tujuan dan tidak ada uang yang dipindahkan.</p>
                @elseif ($rekeningSiap)
                    <div class="pemisah tumpuk rapat">
                        <span class="bantu">Transfer ke</span>
                        <b class="angka">{{ config('lilin.rekening.bank') }} ·
                            {{ config('lilin.rekening.nomor') }}</b>
                        <span class="kecil">a.n. {{ config('lilin.rekening.nama') }}. Transfer sesuai nominal durasi yang dipilih di atas.</span>
                    </div>
                @else
                    <p class="pesan catatan">Pembayaran premium belum dibuka karena rekening tujuan belum diatur. Hubungi admin lewat halaman <a href="{{ route('bantuan') }}">Bantuan</a>.</p>
                @endif
                <fieldset class="tumpuk">
                    <legend>Rekening Anda (asal transfer)</legend>
                    <p class="bantu">{{ $simulasi ? 'Pilih bank dan gunakan nomor contoh dengan jumlah digit yang sesuai bank tersebut.' : 'Supaya admin bisa mencocokkan transfer Anda. Pilih bank Anda sendiri dan isi nomor rekening Anda.' }}</p>
                    <div class="kisi-2">
                        <div class="isian">
                            <label for="bank_pengirim">Bank</label>
                            <select id="bank_pengirim" name="bank_pengirim" required>
                                <option value="">Pilih bank</option>
                                @foreach (\App\Support\Bank::DAFTAR as $kode => [$namaBank])
                                    <option value="{{ $kode }}" @selected(old('bank_pengirim', $menunggu?->bank_pengirim) === $kode)>{{ $namaBank }}</option>
                                @endforeach
                            </select>
                            @error('bank_pengirim') <span class="salah">{{ $message }}</span> @enderror
                        </div>
                        <div class="isian">
                            <label for="rekening_pengirim">Nomor rekening</label>
                            <input id="rekening_pengirim" name="rekening_pengirim" type="text" inputmode="numeric" autocomplete="off" maxlength="30" value="{{ old('rekening_pengirim', $menunggu?->rekening_pengirim) }}" placeholder="Hanya angka, tanpa spasi" required>
                            @error('rekening_pengirim') <span class="salah">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="isian">
                        <label for="nama_pengirim">Nama pemilik rekening</label>
                        <input id="nama_pengirim" name="nama_pengirim" type="text" maxlength="60" value="{{ old('nama_pengirim', $menunggu?->nama_pengirim ?? $user->name) }}" required aria-
                        describedby="bantu-nama">
                        <span class="bantu" id="bantu-nama">Nama awal mengikuti akun Anda dan boleh diubah sesuai nama pemilik rekening.</span>
                        @error('nama_pengirim') <span class="salah">{{ $message }}</span> @enderror
                    </div>
                </fieldset>
                <div class="isian">
                    <label for="bukti">{{ $simulasi ? 'Bukti pembayaran' : 'Bukti transfer' }}</label>
                    <input id="bukti" name="bukti" type="file" accept="image/png,image/jpeg" @required(! $menunggu) aria-describedby="bantu-bukti">
                    <span class="bantu" id="bantu-bukti">JPG atau PNG, paling besar 2 MB.</span>
                    @error('bukti') <span class="salah">{{ $message }}</span> @enderror
                </div>
                <p><button class="tombol besar" type="submit" @disabled(! $menunggu && ! $simulasi && ! $rekeningSiap)>{{ $menunggu ? 'Simpan perbaikan pengajuan' : ($simulasi ? 'Kirim pengajuan' : 'Kirim bukti pembayaran') }}</button></p>
            </form>
        @else
            <section class="kartu lapang tumpuk">
                <h2>Premium Anda masih aktif</h2>
                <p>Anda sudah dapat memakai seluruh manfaat premium. Pengajuan baru dibuka setelah masa paket berakhir, sehingga tidak ada pembayaran langganan ganda.</p>
                <p><a class="tombol kedua" href="{{ route('analisis.create') }}">Mulai analisis chart</a></p>
            </section>
        @endif
        <aside class="kartu tumpuk longgar">
            <section class="tumpuk rapat">
                <h2>Status paket</h2>
                @if ($premium)
                    <p><span class="tanda tunggu">Premium aktif</span></p>
                    <p class="kecil">Berlaku sampai {{ $user->premium_sampai->translatedFormat('j F Y') }}. Pengajuan berikutnya dibuka setelah masa premium berakhir.</p>
                    <p class="kecil">Kuota analisis bulan ini: <b class="angka">{{ $user->sisaKuota() }}</b> dari {{ $user->batasKuota() }} tersisa.</p>
                @else
                    <p><span class="tanda">Member biasa</span></p>
                    <p class="kecil">{{ \App\Models\User::KUOTA_HARIAN }} analisis per hari. Upgrade untuk {{ config('lilin.kuota_premium_bulanan') }} analisis per bulan, review mentor, dan riwayat lengkap.</p>
                @endif
                @if ($menunggu)
                    <p class="pesan catatan">Pembayaran {{ $menunggu->durasi_bulan }} bulan yang Anda kirim {{ $menunggu->created_at->diffForHumans() }} sedang menunggu pemeriksaan admin.</p>
                    <form method="POST" action="{{ route('paket.destroy', $menunggu) }}" onsubmit="return confirm('Batalkan pengajuan ini? Bukti transfer yang diunggah ikut dihapus.')">
                        @csrf
                        @method('DELETE')
                        <button class="tombol bahaya kecil" type="submit">Batalkan pengajuan</button>
                    </form>
                @endif
            </section>
            @if ($riwayat->isNotEmpty())
                <section class="tumpuk rapat">
                    <h2>Riwayat pembayaran</h2>
                    <ul class="daftar">
                        @foreach ($riwayat as $p)
                            <li class="antara kecil">
                                <span>{{ $p->durasi_bulan }} bulan · {{ $p->created_at->translatedFormat('j M Y') }}@if ($p->simulasi) <span class="bantu">· Uji coba</span>@endif</span>
                                <span class="tanda {{ ['disetujui' => 'naik', 'ditolak' => 'turun', 'dicabut' => 'turun', 'menunggu' => 'tunggu'][$p->status] }}">{{ ucfirst($p->status) }}</span>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif
        </aside>
    </div>
@endsection