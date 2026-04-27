<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nmi_payment_orders', function (Blueprint $table) {
            $table->string('nmi_invoice_id')->nullable()->index()->after('nmi_order_id');
        });
    }

    public function down(): void
    {
        Schema::table('nmi_payment_orders', function (Blueprint $table) {
            $table->dropColumn('nmi_invoice_id');
        });
    }
};
