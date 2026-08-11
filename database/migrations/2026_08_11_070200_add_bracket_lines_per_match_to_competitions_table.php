<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Berapa peserta/tim tanding sekaligus per heat di bagan turnamen lomba ini
     * (2 = head-to-head klasik, >2 = beberapa line sekaligus). Diisi panitia saat
     * generate bagan pertama kali; null = lomba ini belum/tidak pakai bagan.
     */
    public function up(): void
    {
        Schema::table('competitions', function (Blueprint $table) {
            $table->integer('bracket_lines_per_match')->nullable()->after('total_rounds');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('competitions', function (Blueprint $table) {
            $table->dropColumn('bracket_lines_per_match');
        });
    }
};
