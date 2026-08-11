<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Partai perebutan Juara 3 (mode "vs") — heat khusus di babak final yang
     * mempertemukan kedua kalah semifinal, terpisah dari heat final biasa.
     */
    public function up(): void
    {
        Schema::table('competition_matches', function (Blueprint $table) {
            $table->boolean('is_third_place')->default(false)->after('heat_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('competition_matches', function (Blueprint $table) {
            $table->dropColumn('is_third_place');
        });
    }
};
