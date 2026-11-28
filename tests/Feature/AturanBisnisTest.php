<?php

namespace Tests\Feature;

use App\Models\Analisis;
use App\Models\Pembayaran;
use App\Models\Review;
use App\Models\Tantangan;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AturanBisnisTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Storage::fake('local');
        $this->seed();
    }

    private function akun(string $nama): User
    {
        return User::where('email', "{$nama}@contoh.id")->firstOrFail();
    }

    private function isianBayar(array $lebih = []): array
    {
        return $lebih + ['durasi_bulan' => '1', 'bank_pengirim' => 'bca', 'rekening_pengirim' => '1111111111', 'nama_pengirim' => 'Pengirim Uji', 'bukti' => $this->chart()];
    }

    private function chart(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('chart.png', file_get_contents(database_path('seeders/gambar/chart-naik.png')));
    }

    private function bayar(User $user, int $bulan = 1): Pembayaran
    {
        return Pembayaran::create(['user_id' => $user->id, 'durasi_bulan' => $bulan, 'jumlah' => 333000 * $bulan, 'bukti' => 'bukti/x.png', 'status' => 'menunggu']);
    }

    private function analisis(User $user, array $lebih = []): Analisis
    {
        Storage::disk('public')->put('chart/x.png', file_get_contents(database_path('seeders/gambar/chart-naik.png')));

        return Analisis::forceCreate($lebih + ['user_id' => $user->id, 'gambar' => 'chart/x.png', 'kode_saham' => 'TEST', 'rentang' => '1D', 'status' => 'selesai']);
    }

    private function review(Analisis $a, User $mentor, array $lebih = []): Review
    {
        $a->update(['status_review' => 'menunggu']);

        return Review::create($lebih + ['analisis_id' => $a->id, 'mentor_id' => $mentor->id, 'status' => 'menunggu', 'ditugaskan_pada' => now()]);
    }

    private function palsukanGemini(int $status = 200): void
    {
        $jawaban = json_encode([
            'status' => 'ok', 'tren' => 'naik', 'support' => ['harga' => 4790, 'kekuatan' => 'kuat'],
            'resistance' => ['harga' => '5.200', 'kekuatan' => 'lemah'], 'pola' => ['higher low'],
            'channel' => ['ada' => false, 'arah' => 'datar', 'posisi_harga' => '-'], 'keyakinan' => 'sedang',
            'penjelasan' => 'x', 'skenario_naik' => 'a', 'skenario_turun' => 'b', 'tidak_terbaca' => '-',
        ]);
        Http::fake(['generativelanguage.googleapis.com/*' => $status === 200
            ? Http::response(['candidates' => [['content' => ['parts' => [['text' => $jawaban]]]]]])
            : Http::response(['error' => ['message' => 'x']], $status)]);
    }

    public function test_t1_persetujuan_ganda_hanya_memperpanjang_sekali(): void
    {
        $admin = $this->akun('rahmat');
        $p = $this->bayar($this->akun('nadia'));

        $salinanA = Pembayaran::find($p->id);
        $salinanB = Pembayaran::find($p->id);

        $this->assertTrue($salinanA->setujui($admin));
        $this->assertFalse($salinanB->setujui($admin));

        $this->assertTrue($this->akun('nadia')->premium_sampai->isSameDay(today()->addMonthNoOverflow()));
    }

    public function test_t1_klik_kedua_di_admin_ditolak_dengan_pesan(): void
    {
        $admin = $this->akun('rahmat');
        $p = $this->bayar($this->akun('nadia'));

        $this->actingAs($admin)->put("/admin/pembayaran/{$p->id}", ['aksi' => 'setujui'])->assertSessionHasNoErrors();
        $this->actingAs($admin)->put("/admin/pembayaran/{$p->id}", ['aksi' => 'setujui'])->assertSessionHasErrors('pembayaran');
        $this->actingAs($admin)->put("/admin/pembayaran/{$p->id}", ['aksi' => 'tolak'])->assertSessionHasErrors('pembayaran');

        $this->assertSame('disetujui', $p->fresh()->status);
        $this->assertTrue($this->akun('nadia')->premium_sampai->isSameDay(today()->addMonthNoOverflow()));
    }

    public function test_t2_database_menolak_dua_pembayaran_menunggu_untuk_satu_member(): void
    {
        $nadia = $this->akun('nadia');
        $this->bayar($nadia);

        $this->expectException(UniqueConstraintViolationException::class);
        $this->bayar($nadia);
    }

    public function test_t2_pembayaran_lama_yang_sudah_diperiksa_tidak_menghalangi_yang_baru(): void
    {
        $nadia = $this->akun('nadia');
        $p = $this->bayar($nadia);
        $p->tolak($this->akun('rahmat'));

        $this->assertNotNull($this->bayar($nadia)->id);
    }

    public function test_t3_nominal_tercatat_saat_dikirim_dan_tidak_ikut_berubah_bila_harga_diganti(): void
    {
        config(['lilin.rekening' => ['bank' => 'BCA', 'nomor' => '123', 'nama' => 'Lilin']]);
        $this->actingAs($this->akun('nadia'))->post('/paket', $this->isianBayar(['durasi_bulan' => 3]))->assertSessionHasNoErrors();

        $p = Pembayaran::where('user_id', $this->akun('nadia')->id)->where('status', 'menunggu')->firstOrFail();
        $this->assertSame(999000, (int) $p->jumlah);

        config(['lilin.harga_per_bulan' => 500000]);
        $this->assertSame(999000, $p->fresh()->nominal());
    }

    public function test_t3_cabut_premium_memundurkan_masa_berlaku_dan_tercatat(): void
    {
        $admin = $this->akun('rahmat');
        $nadia = $this->akun('nadia');
        $p = $this->bayar($nadia);
        $p->setujui($admin);
        $this->assertTrue($nadia->fresh()->isPremium());

        $this->actingAs($admin)->put("/admin/pembayaran/{$p->id}/cabut")->assertSessionHasNoErrors();

        $this->assertFalse($nadia->fresh()->isPremium());
        $this->assertSame('biasa', $nadia->fresh()->paket);
        $this->assertSame('dicabut', $p->fresh()->status);
        $this->assertDatabaseHas('catatan_admin', ['tindakan' => 'Cabut premium']);

        $this->actingAs($admin)->put("/admin/pembayaran/{$p->id}/cabut")->assertSessionHasErrors('pembayaran');
    }

    public function test_pembayaran_kedua_tidak_memperpanjang_premium_yang_masih_aktif(): void
    {
        $admin = $this->akun('rahmat');
        $nadia = $this->akun('nadia');
        $a = $this->bayar($nadia);
        $this->assertTrue($a->setujui($admin));
        $sampai = $nadia->fresh()->premium_sampai->toDateString();
        $b = $this->bayar($nadia);
        $this->assertFalse($b->setujui($admin));
        $this->assertSame($sampai, $nadia->fresh()->premium_sampai->toDateString());
        $this->assertSame('menunggu', $b->fresh()->status);
    }

    public function test_pencabutan_pembayaran_lama_tidak_mengubah_langganan_baru(): void
    {
        $admin = $this->akun('rahmat');
        $member = $this->akun('nadia');
        $lama = $this->bayar($member);
        $this->assertTrue($lama->setujui($admin));
        $member->update(['premium_sampai' => today()->subDay()]);
        $baru = $this->bayar($member);
        $this->assertTrue($baru->setujui($admin));
        $sampai = $member->fresh()->premium_sampai->toDateString();

        $this->assertFalse($lama->cabut($admin));
        $this->assertSame($sampai, $member->fresh()->premium_sampai->toDateString());
        $this->assertSame('disetujui', $lama->fresh()->status);
        $this->assertTrue($baru->cabut($admin));
        $this->assertFalse($lama->cabut($admin));
    }

    public function test_t3_member_tidak_bisa_mencabut(): void
    {
        $p = $this->bayar($this->akun('nadia'));
        $p->setujui($this->akun('rahmat'));

        $this->actingAs($this->akun('bima'))->put("/admin/pembayaran/{$p->id}/cabut")->assertForbidden();
    }

    public function test_t4_pemilik_analisis_tidak_dipilih_sebagai_pengganti(): void
    {
        $dina = $this->akun('dina');
        $laras = $this->akun('laras');
        $this->akun('yoga')->update(['status' => 'nonaktif']);

        $a = $this->analisis($laras);
        $r = $this->review($a, $dina, ['ditugaskan_pada' => now()->subHours(2)]);

        $this->actingAs($this->akun('rahmat'))->put("/admin/review/{$r->id}/pindah")->assertSessionHasErrors('review');

        $this->assertSame($dina->id, $r->fresh()->mentor_id, 'Laras (pemilik) tidak boleh menerima review analisisnya sendiri');
    }

    public function test_t4_permintaan_review_tidak_jatuh_ke_pemilik_analisis(): void
    {
        $this->akun('dina')->update(['status' => 'nonaktif']);
        $this->akun('yoga')->update(['status' => 'nonaktif']);
        $laras = $this->akun('laras');
        $laras->update(['role' => 'mentor']);

        $this->assertNull(User::mentorPalingLuang([$laras->id]));
    }

    public function test_t5_member_bisa_membatalkan_review_yang_melewati_batas(): void
    {
        $nadia = $this->akun('bima');
        $nadia->update(['paket' => 'premium', 'premium_sampai' => today()->addDays(10)]);
        $a = $this->analisis($nadia);
        $r = $this->review($a, $this->akun('dina'), ['ditugaskan_pada' => now()->subHours(3)]);

        $this->actingAs($nadia)->get("/analisis/{$a->id}")->assertOk()->assertSee('Batalkan permintaan review');
        $this->actingAs($nadia)->post("/review/{$r->id}/batal")->assertSessionHasNoErrors();

        $this->assertNull(Review::find($r->id));
        $this->assertSame('tidak_diminta', $a->fresh()->status_review);
        $this->assertTrue($a->fresh()->bolehDihapus());
    }

    public function test_t5_review_yang_belum_melewati_batas_tidak_bisa_dibatalkan(): void
    {
        $bima = $this->akun('bima');
        $a = $this->analisis($bima);
        $r = $this->review($a, $this->akun('dina'));

        $this->actingAs($bima)->post("/review/{$r->id}/batal")->assertSessionHasErrors('review');
        $this->assertNotNull(Review::find($r->id));
    }

    public function test_t5_member_lain_tidak_bisa_membatalkan_review_orang(): void
    {
        $a = $this->analisis($this->akun('bima'));
        $r = $this->review($a, $this->akun('dina'), ['ditugaskan_pada' => now()->subHours(3)]);

        $this->actingAs($this->akun('tari'))->post("/review/{$r->id}/batal")->assertForbidden();
    }

    public function test_t5_admin_memindahkan_review_macet_ke_mentor_lain(): void
    {
        $a = $this->analisis($this->akun('bima'));
        $r = $this->review($a, $this->akun('dina'), ['ditugaskan_pada' => now()->subHours(3)]);

        $this->actingAs($this->akun('rahmat'))->get('/admin')->assertOk()->assertSee('Pindahkan ke mentor lain');
        $this->actingAs($this->akun('rahmat'))->put("/admin/review/{$r->id}/pindah")->assertSessionHasNoErrors();

        $baru = $r->fresh();
        $this->assertNotSame($this->akun('dina')->id, $baru->mentor_id);
        $this->assertFalse($baru->terlambat());
        $this->assertDatabaseHas('catatan_admin', ['tindakan' => 'Pindahkan review']);
    }

    public function test_t5_admin_diberi_tahu_bila_tidak_ada_pengganti(): void
    {
        $this->akun('yoga')->update(['status' => 'nonaktif']);
        $this->akun('laras')->update(['status' => 'nonaktif']);
        $a = $this->analisis($this->akun('bima'));
        $r = $this->review($a, $this->akun('dina'), ['ditugaskan_pada' => now()->subHours(3)]);

        $this->actingAs($this->akun('rahmat'))->put("/admin/review/{$r->id}/pindah")->assertSessionHasErrors('review');
        $this->assertSame($this->akun('dina')->id, $r->fresh()->mentor_id);
    }

    public function test_t5_menonaktifkan_mentor_langsung_memindahkan_review_yang_ia_pegang(): void
    {
        $dina = $this->akun('dina');
        $a = $this->analisis($this->akun('bima'));
        $r = $this->review($a, $dina);

        $this->actingAs($this->akun('rahmat'))
            ->put("/admin/pengguna/{$dina->id}", ['role' => 'mentor', 'status' => 'nonaktif'])->assertSessionHasNoErrors();

        $this->assertNotSame($dina->id, $r->fresh()->mentor_id);
        $this->assertSame('menunggu', $r->fresh()->status);
    }

    public function test_t5_menurunkan_mentor_menjadi_member_juga_memindahkan_review(): void
    {
        $dina = $this->akun('dina');
        $r = $this->review($this->analisis($this->akun('bima')), $dina);

        $this->actingAs($this->akun('rahmat'))
            ->put("/admin/pengguna/{$dina->id}", ['role' => 'member', 'status' => 'aktif'])->assertSessionHasNoErrors();

        $this->assertNotSame($dina->id, $r->fresh()->mentor_id);
    }

    private function tantanganBaru(string $status): Tantangan
    {
        return Tantangan::forceCreate([
            'mentor_id' => $this->akun('dina')->id, 'judul' => 'Uji', 'gambar_potong' => 'tantangan/p.png',
            'gambar_lanjutan' => 'tantangan/l.png', 'jawaban_benar' => 'naik',
            'ditutup_pada' => now()->addDays(2), 'status' => $status,
        ]);
    }

    public function test_t6_gambar_tantangan_yang_belum_disetujui_hanya_untuk_admin_dan_pembuatnya(): void
    {
        Storage::disk('local')->put('tantangan/p.png', 'x');
        $t = $this->tantanganBaru('diajukan');

        $this->actingAs($this->akun('nadia'))->get("/tantangan/{$t->id}/gambar/potong")->assertForbidden();
        $this->actingAs($this->akun('rahmat'))->get("/tantangan/{$t->id}/gambar/potong")->assertOk();
        $this->actingAs($this->akun('dina'))->get("/tantangan/{$t->id}/gambar/potong")->assertOk();
        $this->actingAs($this->akun('yoga'))->get("/tantangan/{$t->id}/gambar/potong")->assertForbidden();

        $t->update(['status' => 'disetujui']);
        $this->actingAs($this->akun('nadia'))->get("/tantangan/{$t->id}/gambar/potong")->assertOk();
        $this->actingAs($this->akun('nadia'))->get("/tantangan/{$t->id}/gambar/lanjutan")->assertForbidden();
    }

    public function test_t6_akun_nonaktif_dengan_sesi_hidup_tidak_bisa_mengambil_gambar(): void
    {
        Storage::disk('local')->put('tantangan/p.png', 'x');
        $t = $this->tantanganBaru('disetujui');
        $nadia = $this->akun('nadia');

        $this->actingAs($nadia)->get("/tantangan/{$t->id}/gambar/potong")->assertOk();

        $nadia->update(['status' => 'nonaktif']);
        $this->actingAs($nadia->fresh())->get("/tantangan/{$t->id}/gambar/potong")->assertRedirect();
    }

    public function test_t7_ulangi_berbagi_batas_laju_dengan_analisis_baru(): void
    {
        $this->palsukanGemini(500);
        $fajar = $this->akun('fajar');
        $a = $this->analisis($fajar, ['status' => 'gagal']);

        $kode = [];
        for ($i = 0; $i < 11; $i++) {
            $kode[] = $this->actingAs($fajar)->post("/analisis/{$a->id}/ulang")->getStatusCode();
        }

        $this->assertSame(429, end($kode));
        $this->assertNotContains(429, array_slice($kode, 0, 10));
    }

    public function test_t8_analisis_gagal_kemarin_yang_berhasil_diulang_memakai_jatah_hari_ini(): void
    {
        $this->palsukanGemini();
        $nadia = $this->akun('nadia');
        $a = $this->analisis($nadia, ['status' => 'gagal', 'created_at' => now()->subDay(), 'updated_at' => now()->subDay()]);
        $sebelum = $nadia->analisisHariIni();
        $sisaSebelum = $nadia->sisaKuota();
        $this->assertGreaterThan(0, $sisaSebelum);

        $this->actingAs($nadia)->post("/analisis/{$a->id}/ulang")->assertSessionHasNoErrors();

        $this->assertSame('selesai', $a->fresh()->status);
        $this->assertSame($sebelum + 1, $nadia->fresh()->analisisHariIni());
        $this->assertSame($sisaSebelum - 1, $nadia->fresh()->sisaKuota());
    }

    public function test_t8_ulangi_ditolak_bila_jatah_hari_ini_habis(): void
    {
        $this->palsukanGemini();
        $nadia = $this->akun('nadia');
        while ($nadia->sisaKuota() > 0) {
            $this->analisis($nadia);
        }
        $a = $this->analisis($nadia, ['status' => 'gagal', 'created_at' => now()->subDay()]);

        $this->actingAs($nadia)->post("/analisis/{$a->id}/ulang")->assertSessionHasErrors('ulang');
        $this->assertSame('gagal', $a->fresh()->status);
        Http::assertNothingSent();
    }

    public function test_t8_premium_ulang_dari_bulan_lalu_memakai_jatah_bulan_ini(): void
    {
        $this->palsukanGemini();
        $fajar = $this->akun('fajar');
        $a = $this->analisis($fajar, ['status' => 'gagal', 'created_at' => now()->startOfMonth()->subDay()]);
        $sebelum = $fajar->analisisBulanIni();

        $this->actingAs($fajar)->post("/analisis/{$a->id}/ulang")->assertSessionHasNoErrors();

        $this->assertSame($sebelum + 1, $fajar->fresh()->analisisBulanIni());
    }

    public function test_t9_ulangi_pada_analisis_yang_sedang_diproses_tidak_memanggil_ai_lagi(): void
    {
        $this->palsukanGemini();
        $nadia = $this->akun('nadia');
        $a = $this->analisis($nadia, ['status' => 'diproses']);

        $this->actingAs($nadia)->post("/analisis/{$a->id}/ulang")->assertRedirect();

        Http::assertNothingSent();
        $this->assertSame('diproses', $a->fresh()->status);
    }

    public function test_t9_pengiriman_pada_sisa_kuota_terakhir_hanya_lolos_sekali(): void
    {
        $this->palsukanGemini();
        $nadia = $this->akun('nadia');
        while ($nadia->sisaKuota() > 1) {
            $this->analisis($nadia);
        }
        $fileSebelum = count(Storage::disk('public')->allFiles('chart'));

        $this->actingAs($nadia)->post('/analisis', ['gambar' => $this->chart(), 'rentang' => '1D', 'tebak_tren' => 'naik'])->assertSessionHasNoErrors();
        $this->actingAs($nadia)->post('/analisis', ['gambar' => $this->chart(), 'rentang' => '1D', 'tebak_tren' => 'naik'])->assertSessionHasErrors('gambar');

        $this->assertSame(0, $nadia->fresh()->sisaKuota());
        $this->assertCount($fileSebelum + 1, Storage::disk('public')->allFiles('chart'));
    }

    public function test_t10_review_dikirim_dua_kali_tidak_menimpa_yang_pertama(): void
    {
        $dina = $this->akun('dina');
        $a = $this->analisis($this->akun('bima'));
        $r = $this->review($a, $dina);

        $this->actingAs($dina)->put("/mentor/review/{$r->id}", ['penilaian' => 'benar'])->assertSessionHasNoErrors();
        $this->actingAs($dina)->put("/mentor/review/{$r->id}", ['penilaian' => 'salah', 'catatan' => 'ubah'])->assertRedirect();

        $this->assertSame('benar', $r->fresh()->penilaian);
    }


    public function test_t10_permintaan_review_ganda_tidak_menghasilkan_galat(): void
    {
        $fajar = $this->akun('fajar');
        $a = $this->analisis($fajar);

        $this->actingAs($fajar)->post("/analisis/{$a->id}/review")->assertSessionHasNoErrors();
        $this->actingAs($fajar)->post("/analisis/{$a->id}/review")->assertStatus(302);

        $this->assertSame(1, Review::where('analisis_id', $a->id)->count());
    }

    public function test_rekening_pengirim_wajib_dan_sesuai_bank(): void
    {
        $nadia = $this->akun('nadia');

        $this->actingAs($nadia)->post('/paket', $this->isianBayar(['bank_pengirim' => '']))->assertSessionHasErrors('bank_pengirim');
        $this->actingAs($nadia)->post('/paket', $this->isianBayar(['bank_pengirim' => 'bank-ngawur']))->assertSessionHasErrors('bank_pengirim');
        $this->actingAs($nadia)->post('/paket', $this->isianBayar(['rekening_pengirim' => '']))->assertSessionHasErrors('rekening_pengirim');
        $this->actingAs($nadia)->post('/paket', $this->isianBayar(['nama_pengirim' => '']))->assertSessionHasErrors('nama_pengirim');
        $this->actingAs($nadia)->post('/paket', $this->isianBayar(['rekening_pengirim' => '111111111']))->assertSessionHasErrors('rekening_pengirim');
        $this->actingAs($nadia)->post('/paket', $this->isianBayar(['bank_pengirim' => 'btn', 'rekening_pengirim' => '1111111111']))->assertSessionHasErrors('rekening_pengirim');
        $this->assertSame(0, Pembayaran::where('user_id', $nadia->id)->count());
    }

    public function test_rekening_pengirim_tersimpan_dibersihkan_dan_tampil_di_admin(): void
    {
        $nadia = $this->akun('nadia');

        $this->actingAs($nadia)->post('/paket', $this->isianBayar([
            'bank_pengirim' => 'mandiri', 'rekening_pengirim' => '1370-0123 45678', 'nama_pengirim' => 'Nadia Putri',
        ]))->assertSessionHasNoErrors();

        $p = Pembayaran::where('user_id', $nadia->id)->firstOrFail();
        $this->assertSame('mandiri', $p->bank_pengirim);
        $this->assertSame('1370012345678', $p->rekening_pengirim);
        $this->assertSame('Nadia Putri', $p->nama_pengirim);

        $this->actingAs($this->akun('rahmat'))->get('/admin')->assertOk()
            ->assertSee('Bank Mandiri · 1370012345678')->assertSee('a.n. Nadia Putri');
    }

    public function test_halaman_paket_menampilkan_pilihan_bank(): void
    {
        $this->actingAs($this->akun('nadia'))->get('/paket')->assertOk()
            ->assertSee('Rekening Anda (asal transfer)')->assertSee('BCA')->assertSee('Bank Mandiri')->assertSee('BRI')->assertSee('SeaBank');
    }

    public function test_banyak_pengguna_satu_jaringan_tidak_saling_memblokir_saat_masuk(): void
    {
        foreach (['nadia', 'bima', 'tari', 'fajar', 'sari'] as $nama) {
            for ($i = 0; $i < 5; $i++) {
                $this->post('/masuk', ['email' => "{$nama}@contoh.id", 'password' => 'salah'])->assertStatus(302);
            }
        }

        $this->post('/masuk', ['email' => 'nadia@contoh.id', 'password' => 'salah'])->assertStatus(429);
        $this->post('/masuk', ['email' => 'dina@contoh.id', 'password' => 'rahasia123'])->assertRedirect();
        $this->assertAuthenticatedAs($this->akun('dina'));
    }

    public function test_banyak_pendaftar_dari_satu_jaringan_tidak_langsung_diblokir(): void
    {
        for ($i = 1; $i <= 12; $i++) {
            $this->post('/daftar', ['name' => "Pendaftar {$i}", 'email' => "baru{$i}@contoh.id", 'password' => 'abcd1234', 'password_confirmation' => 'abcd1234', 'setuju' => 1])
                ->assertSessionHasNoErrors();
            $this->post('/keluar');
        }

        $this->assertSame(12, User::where('email', 'like', 'baru%@contoh.id')->count());
    }

    public function test_perintah_buat_admin_membuat_akun_baru_dan_menjadikan_akun_lama_admin(): void
    {
        $this->artisan('lilin:buat-admin', ['email' => 'Pemilik@Lilin.ID', '--nama' => 'Pemilik'])
            ->expectsQuestion('Kata sandi admin (minimal 8 karakter)', 'sandi-panjang-1')
            ->assertSuccessful();

        $admin = User::where('email', 'pemilik@lilin.id')->firstOrFail();
        $this->assertSame('admin', $admin->role);
        $this->assertSame('Pemilik', $admin->name);
        $this->post('/masuk', ['email' => 'pemilik@lilin.id', 'password' => 'sandi-panjang-1'])->assertRedirect();
        $this->assertAuthenticatedAs($admin);
        $this->post('/keluar');

        $this->artisan('lilin:buat-admin', ['email' => 'nadia@contoh.id'])->assertSuccessful();
        $this->assertSame('admin', $this->akun('nadia')->role);
        $this->post('/masuk', ['email' => 'nadia@contoh.id', 'password' => 'rahasia123'])->assertRedirect();
    }

    public function test_perintah_buat_admin_menolak_sandi_pendek_dan_email_salah(): void
    {
        $this->artisan('lilin:buat-admin', ['email' => 'x@lilin.id'])
            ->expectsQuestion('Kata sandi admin (minimal 8 karakter)', 'pendek')
            ->assertFailed();
        $this->assertNull(User::where('email', 'x@lilin.id')->first());

        $this->artisan('lilin:buat-admin', ['email' => 'bukan-email'])->assertFailed();
    }

    public function test_halaman_admin_tetap_ringan_dengan_antrean_panjang_dan_angka_tetap_benar(): void
    {
        $sudahAda = Tantangan::where('status', 'diajukan')->count();
        for ($i = 0; $i < 30; $i++) {
            Tantangan::forceCreate([
                'mentor_id' => $this->akun('dina')->id, 'judul' => "Antre {$i}", 'gambar_potong' => 'tantangan/p.png',
                'gambar_lanjutan' => 'tantangan/l.png', 'jawaban_benar' => 'naik', 'ditutup_pada' => now()->addDays(3), 'status' => 'diajukan',
            ]);
        }

        $html = $this->actingAs($this->akun('rahmat'))->get('/admin')->assertOk()
            ->assertSee('Menampilkan 25 yang paling lama dari ' . ($sudahAda + 30))->getContent();

        $this->assertSame(25, substr_count($html, 'Chart dipotong untuk '));
    }
}