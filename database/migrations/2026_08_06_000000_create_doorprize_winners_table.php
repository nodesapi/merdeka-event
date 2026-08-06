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
        Schema::create('doorprize_winners', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('event_id');
            $table->uuid('family_member_id');
            $table->string('prize_name')->nullable();
            $table->timestamp('drawn_at');
            $table->timestamps();

            $table->foreign('event_id')
                ->references('id')
                ->on('events')
                ->cascadeOnDelete();

            $table->foreign('family_member_id')
                ->references('id')
                ->on('family_members')
                ->cascadeOnDelete();

            // Satu kepala keluarga hanya boleh menang sekali per acara.
            $table->unique(['event_id', 'family_member_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('doorprize_winners');
    }
};
