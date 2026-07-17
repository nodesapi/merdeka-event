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
        // Lomba bisa berupa individu (per orang, seperti sekarang) atau grup (per tim keluarga).
        Schema::table('competitions', function (Blueprint $table) {
            $table->enum('type', ['individual', 'group'])->default('individual')->after('slug');
            $table->integer('min_team_members')->nullable()->after('max_age');
            $table->integer('max_team_size')->nullable()->after('min_team_members');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('competitions', function (Blueprint $table) {
            $table->dropColumn(['type', 'min_team_members', 'max_team_size']);
        });
    }
};
