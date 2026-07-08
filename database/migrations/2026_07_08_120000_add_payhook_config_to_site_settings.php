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
        Schema::table('site_settings', function (Blueprint $table) {
            $table->boolean('payhook_enabled')->default(false)->after('bank_account_holder');
            $table->string('payhook_base_url')->nullable()->after('payhook_enabled');
            // API key & webhook secret disimpan terenkripsi (cast 'encrypted' di model) -> pakai text.
            $table->text('payhook_api_key')->nullable()->after('payhook_base_url');
            $table->text('payhook_webhook_secret')->nullable()->after('payhook_api_key');
            $table->string('payhook_channel_type')->default('qris')->after('payhook_webhook_secret');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn([
                'payhook_enabled',
                'payhook_base_url',
                'payhook_api_key',
                'payhook_webhook_secret',
                'payhook_channel_type',
            ]);
        });
    }
};
