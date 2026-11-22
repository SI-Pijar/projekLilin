<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tantangan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mentor_id')->constrained('users')->cascadeOnDelete();
            $table->string('judul', 120);
            $table->string('gambar_potong');
            $table->string('gambar_lanjutan');
            $table->enum('jawaban_benar', ['naik', 'datar', 'turun']);
            $table->text('pembahasan')->nullable();
            $table->timestamp('ditutup_pada');
            $table->enum('status', ['diajukan', 'disetujui', 'ditolak', 'dibuka'])->default('diajukan');
            $table->timestamps();
        });

        Schema::create('jawaban_tantangan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tantangan_id')->constrained('tantangan')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('jawaban', ['naik', 'datar', 'turun']);
            $table->string('alasan', 200)->nullable();
            $table->unsignedSmallInteger('poin')->default(0);
            $table->timestamps();
            $table->unique(['tantangan_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jawaban_tantangan');
        Schema::dropIfExists('tantangan');
    }
};