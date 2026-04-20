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
        Schema::create('ghl_clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ghl_location_id');
            $table->string('ghl_contact_id');
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->json('tags')->nullable();
            $table->dateTime('last_activity_at')->nullable();
            $table->json('raw')->nullable();
            $table->timestamps();

            $table->unique(['ghl_location_id', 'ghl_contact_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ghl_clients');
    }
};
