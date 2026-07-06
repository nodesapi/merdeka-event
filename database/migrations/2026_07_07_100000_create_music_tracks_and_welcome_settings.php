<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('music_tracks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->string('path');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::table('site_settings', function (Blueprint $table) {
            $table->boolean('welcome_enabled')->default(true)->after('terms_conditions');
            $table->string('welcome_title')->nullable()->after('welcome_enabled');
            $table->text('welcome_message')->nullable()->after('welcome_title');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn(['welcome_enabled', 'welcome_title', 'welcome_message']);
        });

        Schema::dropIfExists('music_tracks');
    }
};
