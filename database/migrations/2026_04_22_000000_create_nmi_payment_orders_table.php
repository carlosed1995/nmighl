<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nmi_payment_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('ghl_client_id')->nullable()->constrained('ghl_clients')->nullOnDelete();
            $table->string('ghl_contact_id')->nullable();
            $table->string('ghl_location_id')->nullable();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('USD');
            $table->string('description')->nullable();
            $table->string('status', 32)->default('pending')->index();
            $table->string('nmi_transaction_id')->nullable()->index();
            $table->string('nmi_order_id')->nullable();
            $table->string('response_message')->nullable();
            $table->json('gateway_payload')->nullable();
            $table->json('webhook_payload')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nmi_payment_orders');
    }
};
