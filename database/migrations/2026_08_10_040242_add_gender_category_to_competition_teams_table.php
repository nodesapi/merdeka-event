<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Kategori putra/putri per tim (fairness untuk lomba grup, mis. tarik tambang bapak-bapak vs ibu-ibu).
        // Nullable: tim lama sebelum fitur ini tetap ada, tampil "Tanpa kategori" sampai admin mengaturnya.
        Schema::table('competition_teams', function (Blueprint $table) {
            $table->enum('gender_category', ['L', 'P'])->nullable()->after('team_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('competition_teams', function (Blueprint $table) {
            $table->dropColumn('gender_category');
        });
    }
};
