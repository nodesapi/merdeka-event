<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bagan turnamen harus mengikuti pengelompokan kategori yang sama dengan layar
     * Peserta & Juara (kategori umur untuk lomba individu, Putra/Putri untuk lomba
     * grup) — supaya peserta beda kategori tidak pernah dipertemukan di satu heat.
     * Tiap kategori dapat bagan sendiri-sendiri (heat_number restart per kategori).
     */
    public function up(): void
    {
        Schema::table('competition_matches', function (Blueprint $table) {
            $table->dropUnique(['competition_id', 'round', 'heat_number']);
            $table->string('category_key')->default('none')->after('competition_id');
            $table->unique(['competition_id', 'category_key', 'round', 'heat_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('competition_matches', function (Blueprint $table) {
            $table->dropUnique(['competition_id', 'category_key', 'round', 'heat_number']);
            $table->dropColumn('category_key');
            $table->unique(['competition_id', 'round', 'heat_number']);
        });
    }
};
