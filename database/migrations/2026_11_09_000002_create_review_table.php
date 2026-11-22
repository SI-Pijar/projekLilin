<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('review', function (Blueprint $table) {
            $table->id();
            $table->foreignId('analisis_id')->unique()->constrained('analisis')->cascadeOnDelete();
            $table->foreignId('mentor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['menunggu', 'selesai'])->default('menunggu');
            $table->enum('penilaian', ['benar', 'koreksi', 'salah'])->nullable();
            $table->text('catatan')->nullable();
            $table->timestamp('ditugaskan_pada')->nullable();
            $table->timestamp('direview_pada')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('review');
    }
};

