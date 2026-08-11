<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Daftar peserta/tim di tiap heat — jumlahnya dinamis (2 s/d berapa pun sesuai
     * bracket_lines_per_match), makanya pivot terpisah, bukan kolom tetap di match.
     */
    public function up(): void
    {
        Schema::create('competition_match_entrants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('competition_match_id');
            $table->uuid('entrant_id');
            $table->timestamps();

            $table->foreign('competition_match_id')
                ->references('id')
                ->on('competition_matches')
                ->onDelete('cascade');

            $table->unique(['competition_match_id', 'entrant_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('competition_match_entrants');
    }
};
