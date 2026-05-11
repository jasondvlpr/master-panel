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
        Schema::table('tenants', function (Blueprint $blueprint) {
            $blueprint->json('nameservers')->nullable()->after('domain');
            $blueprint->string('cloudflare_status')->default('pending')->after('nameservers');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $blueprint) {
            $blueprint->dropColumn(['nameservers', 'cloudflare_status']);
        });
    }
};
