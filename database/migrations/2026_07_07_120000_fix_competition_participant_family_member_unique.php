<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Semula family_member_id unik sendiri, sehingga satu anggota keluarga hanya
     * bisa ikut SATU lomba. Ganti jadi unik gabungan (competition_id, family_member_id)
     * agar satu anggota boleh ikut banyak lomba, tapi tidak boleh dobel di lomba yang sama.
     */
    public function up(): void
    {
        Schema::table('competition_participants', function (Blueprint $table) {
            $table->dropUnique(['family_member_id']);
            $table->unique(['competition_id', 'family_member_id']);
        });
    }

    public function down(): void
    {
        Schema::table('competition_participants', function (Blueprint $table) {
            $table->dropUnique(['competition_id', 'family_member_id']);
            $table->unique(['family_member_id']);
        });
    }
};
