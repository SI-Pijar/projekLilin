<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('analisis', function (Blueprint $table) {
            $table->text('catatan')->nullable()->after('rentang');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('analisis', function (Blueprint $table) {
            $table->dropColumn('catatan');
            $table->dropSoftDeletes();
        });
    }
};
