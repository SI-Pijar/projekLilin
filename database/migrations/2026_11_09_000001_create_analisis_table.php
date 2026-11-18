<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analisis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('gambar');
            $table->string('kode_saham', 4)->nullable();
            $table->enum('rentang', ['1H', '1D', '1W'])->default('1D');
            $table->text('catatan')->nullable();
            $table->json('tebakan')->nullable();
            $table->enum('status', ['diproses', 'selesai', 'gagal'])->default('diproses');
            $table->json('hasil')->nullable();
            $table->text('pesan_galat')->nullable();
            $table->string('model_ai', 40)->nullable();
            $table->enum('status_review', ['tidak_diminta', 'menunggu', 'direview', 'final'])->default('tidak_diminta');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analisis');
    }
};
