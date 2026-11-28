<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pembayaran', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('durasi_bulan');
            $table->unsignedInteger('jumlah');
            $table->string('bank_pengirim', 20)->nullable();
            $table->string('rekening_pengirim', 20)->nullable();
            $table->string('nama_pengirim', 60)->nullable();
            $table->string('bukti');
            $table->boolean('simulasi')->default(false);
            $table->enum('status', ['menunggu', 'disetujui', 'ditolak', 'dicabut'])->default('menunggu');
            $table->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('diperiksa_pada')->nullable();
            $table->timestamps();
        });

        DB::statement("CREATE UNIQUE INDEX pembayaran_satu_menunggu ON pembayaran (user_id) WHERE status = 'menunggu'");
    }

    public function down(): void
    {
        Schema::dropIfExists('pembayaran');
    }
};
