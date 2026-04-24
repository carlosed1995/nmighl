<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nmi_payment_orders', function (Blueprint $table) {
            $table->string('ghl_order_id')->nullable()->index()->after('ghl_location_id');
            $table->string('source', 32)->default('manual')->after('description');
            $table->timestamp('synced_to_ghl_at')->nullable()->after('response_message');
            $table->text('ghl_sync_error')->nullable()->after('synced_to_ghl_at');
        });
    }

    public function down(): void
    {
        Schema::table('nmi_payment_orders', function (Blueprint $table) {
            $table->dropColumn(['ghl_order_id', 'source', 'synced_to_ghl_at', 'ghl_sync_error']);
        });
    }
};
