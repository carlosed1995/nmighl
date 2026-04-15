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
        Schema::create('ghl_oauth_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('provider')->default('ghl');
            $table->string('location_id')->nullable()->index();
            $table->string('company_id')->nullable()->index();
            $table->text('access_token');
            $table->text('refresh_token')->nullable();
            $table->string('token_type')->nullable();
            $table->text('scope')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->json('raw')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ghl_oauth_tokens');
    }
};
