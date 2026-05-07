<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ghl_invoices', function (Blueprint $table) {
            $table->string('ghl_invoice_id')->nullable()->unique()->after('ghl_location_id');
            $table->json('raw')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('ghl_invoices', function (Blueprint $table) {
            $table->dropUnique(['ghl_invoice_id']);
            $table->dropColumn(['ghl_invoice_id', 'raw']);
        });
    }
};
