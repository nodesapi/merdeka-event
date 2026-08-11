<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Satu "heat" dalam bagan turnamen — bisa berisi 2 peserta/tim (vs klasik) atau
     * lebih (mis. 5 line sekaligus), tergantung Competition::bracket_lines_per_match.
     * Babak berikutnya dibentuk ULANG dari kumpulan pemenang heat babak ini (bukan
     * pohon match tetap), jadi tidak perlu kolom "match berikutnya".
     */
    public function up(): void
    {
        Schema::create('competition_matches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('competition_id');
            $table->integer('round');
            $table->integer('heat_number');
            $table->uuid('winner_entrant_id')->nullable();
            $table->timestamps();

            $table->foreign('competition_id')
                ->references('id')
                ->on('competitions')
                ->onDelete('cascade');

            $table->unique(['competition_id', 'round', 'heat_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('competition_matches');
    }
};
