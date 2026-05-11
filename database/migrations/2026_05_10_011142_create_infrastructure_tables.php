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
        Schema::create('cloudflare_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->text('api_key')->nullable();
            $table->text('api_token')->nullable();
            $table->timestamps();
        });

        Schema::create('servers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('ip');
            $table->text('api_key');
            $table->string('type')->default('aapanel');
            $table->timestamps();
        });

        Schema::create('managed_domains', function (Blueprint $table) {
            $table->id();
            $table->string('domain_name');
            $table->string('subdomain')->nullable();
            $table->foreignId('cloudflare_account_id')->constrained();
            $table->foreignId('server_id')->constrained();
            $table->string('cloudflare_zone_id')->nullable();
            $table->string('status')->default('pending'); // pending, adding_alias, creating_tenant, configuring_dns, completed, failed
            $table->text('last_error')->nullable();
            $table->timestamps();
        });

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('managed_domain_id')->constrained()->cascadeOnDelete();
            $table->string('step');
            $table->string('status');
            $table->text('message')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('managed_domains');
        Schema::dropIfExists('servers');
        Schema::dropIfExists('cloudflare_accounts');
    }
};
