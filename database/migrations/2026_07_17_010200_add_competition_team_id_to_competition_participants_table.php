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
        // Nullable: hanya diisi kalau peserta ini anggota tim lomba grup.
        Schema::table('competition_participants', function (Blueprint $table) {
            $table->uuid('competition_team_id')->nullable()->after('competition_id');

            $table->foreign('competition_team_id')
                ->references('id')
                ->on('competition_teams')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('competition_participants', function (Blueprint $table) {
            $table->dropForeign(['competition_team_id']);
            $table->dropColumn('competition_team_id');
        });
    }
};
