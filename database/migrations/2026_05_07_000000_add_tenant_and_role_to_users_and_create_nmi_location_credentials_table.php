<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 32)->default('admin')->after('email');
            $table->string('ghl_location_id')->nullable()->index()->after('role');
        });

        Schema::create('nmi_location_credentials', function (Blueprint $table) {
            $table->id();
            $table->string('ghl_location_id')->unique();
            $table->text('api_security_key')->nullable();
            $table->text('webhook_signing_key')->nullable();
            $table->text('webhook_secret')->nullable();
            $table->string('subscription_id')->nullable();
            $table->json('subscribed_events')->nullable();
            $table->timestamp('subscription_last_synced_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nmi_location_credentials');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'ghl_location_id']);
        });
    }
};
