<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ghl_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ghl_client_id')->constrained('ghl_clients')->cascadeOnDelete();
            $table->foreignId('ghl_location_id')->constrained('ghl_locations')->cascadeOnDelete();
            $table->string('invoice_number')->nullable();
            $table->date('issued_date')->nullable();
            $table->date('due_date')->nullable();
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('status', 32)->default('draft')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ghl_invoices');
    }
};
