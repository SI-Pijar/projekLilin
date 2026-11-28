<?php

namespace Tests\Feature;

use App\Models\Analisis;
use App\Models\Pembayaran;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PembayaranDanAksesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake('public');
        Http::preventStrayRequests();
        $this->seed();
    }

    private function akun(string $nama): User
    {
        return User::where('email', "{$nama}@contoh.id")->firstOrFail();
    }

    private function isian(array $tambahan = []): array
    {
        return $tambahan + [
            'durasi_bulan' => '1', 'bank_pengirim' => 'bca', 'rekening_pengirim' => '1111111111',
            'nama_pengirim' => 'Nadia', 'bukti' => UploadedFile::fake()->image('bukti.png'),
        ];
    }

    public function test_fitur_yang_dihapus_tidak_bisa_diakses_langsung(): void
    {
        foreach (['nadia', 'dina', 'rahmat'] as $nama) {
            $this->actingAs($this->akun($nama));
            foreach (['/komunitas', '/komunitas/tulis', '/admin/bank-pola'] as $url) {
                $this->get($url)->assertNotFound();
            }
            $this->post('/review/1/rating', ['rating' => 5])->assertNotFound();
            $this->post('/review/1/banding', ['alasan' => str_repeat('a', 30)])->assertNotFound();
            $this->put('/admin/banding/1', ['keputusan' => 'member_benar'])->assertNotFound();
        }
    }

    public function test_menu_dan_informasi_paket_sesuai_fitur_yang_tersedia(): void
    {
        foreach (['nadia', 'dina', 'rahmat'] as $nama) {
            $user = $this->akun($nama);
            $this->actingAs($user)->get($user->halamanAwal())->assertOk()
                ->assertDontSee('Bank Pola')->assertDontSee('Ruang komunitas')->assertDontSee('Skor mentor');
        }
        $this->actingAs($this->akun('nadia'))->get('/paket')->assertOk()->assertDontSee('banding');
    }

    public function test_premium_aktif_tidak_bisa_mengirim_langganan_baru(): void
    {
        $fajar = $this->akun('fajar');
        $jumlah = Pembayaran::count();
        $sampai = $fajar->premium_sampai->toDateString();
        $this->actingAs($fajar)->get('/paket')->assertOk()->assertSee('Pengajuan baru dibuka setelah masa paket berakhir')
            ->assertDontSee('Kirim bukti pembayaran');
        $this->post('/paket', $this->isian())->assertSessionHasErrors('bukti');
        $this->assertSame($jumlah, Pembayaran::count());
        $this->assertSame($sampai, $fajar->fresh()->premium_sampai->toDateString());
    }

    public function test_premium_kedaluwarsa_bisa_mengajukan_lagi(): void
    {
        $fajar = $this->akun('fajar');
        $fajar->update(['premium_sampai' => today()->subDay()]);
        $this->actingAs($fajar)->post('/paket', $this->isian())->assertSessionHasNoErrors();
        $this->assertDatabaseHas('pembayarans', ['user_id' => $fajar->id, 'status' => 'menunggu']);
    }

    public function test_crud_pembayaran_bisa_dijalankan_sebelum_diperiksa(): void
    {
        $nadia = $this->akun('nadia');
        $this->actingAs($nadia)->post('/paket', $this->isian())->assertSessionHasNoErrors();
        $p = $nadia->pembayaran()->where('status', 'menunggu')->firstOrFail();
        $lama = $p->bukti;
        $this->get('/paket')->assertOk()->assertSee('Perbaiki pengajuan pembayaran');
        $this->put("/paket/{$p->id}", $this->isian(['nama_pengirim' => 'Nadia Putri', 'durasi_bulan' => '3']))->assertSessionHasNoErrors();
        $p->refresh();
        $this->assertSame('Nadia Putri', $p->nama_pengirim);
        $this->assertSame(999000, $p->nominal());
        Storage::disk('local')->assertMissing($lama);
        Storage::disk('local')->assertExists($p->bukti);
        $this->delete("/paket/{$p->id}")->assertSessionHasNoErrors();
        $this->assertModelMissing($p);
        Storage::disk('local')->assertMissing($p->bukti);
    }

    public function test_perbaikan_tanpa_bukti_baru_mempertahankan_file_lama(): void
    {
        $nadia = $this->akun('nadia');
        $this->actingAs($nadia)->post('/paket', $this->isian());
        $p = $nadia->pembayaran()->where('status', 'menunggu')->firstOrFail();
        $this->put("/paket/{$p->id}", $this->isian(['bukti' => null, 'nama_pengirim' => 'Nama diperbaiki']))->assertSessionHasNoErrors();
        $this->assertSame($p->bukti, $p->fresh()->bukti);
        Storage::disk('local')->assertExists($p->bukti);
    }

    public function test_pembayaran_orang_lain_dan_yang_sudah_disetujui_tidak_bisa_diubah(): void
    {
        $nadia = $this->akun('nadia');
        $this->actingAs($nadia)->post('/paket', $this->isian());
        $p = $nadia->pembayaran()->where('status', 'menunggu')->firstOrFail();
        $this->actingAs($this->akun('bima'))->put("/paket/{$p->id}", $this->isian())->assertForbidden();
        $p->setujui($this->akun('rahmat'));
        $this->actingAs($nadia)->put("/paket/{$p->id}", $this->isian())->assertSessionHasErrors('bukti');
        $this->delete("/paket/{$p->id}")->assertSessionHasErrors('bukti');
        $this->assertSame('disetujui', $p->fresh()->status);
    }

    public function test_membuka_halaman_tidak_memindahkan_review_secara_otomatis(): void
    {
        $r = Review::where('status', 'menunggu')->firstOrFail();
        $mentorId = $r->mentor_id;
        $this->travel(60)->minutes();
        $this->actingAs($r->analisis->user)->get("/analisis/{$r->analisis_id}")->assertOk();
        $this->actingAs($r->mentor)->get('/mentor')->assertOk();
        $this->actingAs($this->akun('rahmat'))->get('/admin')->assertOk();
        $this->assertSame($mentorId, $r->fresh()->mentor_id);
        $this->put("/admin/review/{$r->id}/pindah")->assertSessionHasNoErrors();
        $this->assertNotSame($mentorId, $r->fresh()->mentor_id);
        $this->actingAs(User::findOrFail($mentorId))->put("/mentor/review/{$r->id}", ['penilaian' => 'benar'])->assertForbidden();
    }

    public function test_gambar_bukan_chart_tidak_bisa_dimintakan_review(): void
    {
        $a = Analisis::where('user_id', $this->akun('fajar')->id)->where('status_review', 'tidak_diminta')->firstOrFail();
        $a->update(['hasil' => ['status' => 'bukan_chart']]);
        $this->actingAs($a->user)->post("/analisis/{$a->id}/review")->assertRedirect();
        $this->assertNull($a->fresh()->review);
    }
}