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
        Schema::create('cloudflare_domains', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->string('zone_id')->unique();
            $blueprint->foreignId('cloudflare_account_id')->constrained()->cascadeOnDelete();
            $blueprint->string('name');
            $blueprint->string('status');
            $blueprint->json('name_servers')->nullable();
            $blueprint->boolean('paused')->default(false);
            $blueprint->json('subdomains')->nullable();
            $blueprint->timestamp('last_synced_at')->nullable();
            $blueprint->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cloudflare_domains');
    }
};
