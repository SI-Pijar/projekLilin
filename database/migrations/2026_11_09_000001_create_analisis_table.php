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
            $table->string('rentang', 2)->default('1D');
            $table->string('status', 10)->default('diproses');
            $table->json('hasil')->nullable();
            $table->text('pesan_galat')->nullable();
            $table->string('model_ai', 40)->nullable();
            $table->string('status_review', 15)->default('tidak_diminta');
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analisis');
    }
};
