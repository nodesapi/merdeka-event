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
        Schema::table('transactions', function (Blueprint $table) {
            $table->uuid('contribution_item_id')->nullable()->unique()->after('user_id');

            $table->foreign('contribution_item_id')
                ->references('id')
                ->on('contribution_items')
                ->nullOnDelete();
        });

        Schema::table('competition_participants', function (Blueprint $table) {
            $table->uuid('family_member_id')->nullable()->unique()->after('competition_id');

            $table->foreign('family_member_id')
                ->references('id')
                ->on('family_members')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('competition_participants', function (Blueprint $table) {
            $table->dropForeign(['family_member_id']);
            $table->dropColumn('family_member_id');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['contribution_item_id']);
            $table->dropColumn('contribution_item_id');
        });
    }
};
