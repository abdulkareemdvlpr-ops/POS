<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('invoices')) {
            return;
        }

        Schema::table('invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('invoices', 'cashier_id')) {
                $table->foreignId('cashier_id')->nullable()->after('customer_id');
            }

            if (!Schema::hasColumn('invoices', 'service_fee')) {
                $table->decimal('service_fee', 12, 2)->default(0)->after('tax');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('invoices')) {
            return;
        }

        Schema::table('invoices', function (Blueprint $table) {
            if (Schema::hasColumn('invoices', 'cashier_id')) {
                $table->dropColumn('cashier_id');
            }

            if (Schema::hasColumn('invoices', 'service_fee')) {
                $table->dropColumn('service_fee');
            }
        });
    }
};
