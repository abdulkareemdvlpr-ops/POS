<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('business_settings')) {
            return;
        }

        Schema::table('business_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('business_settings', 'delivery_phone')) {
                $table->string('delivery_phone')->nullable()->after('phone');
            }

            if (!Schema::hasColumn('business_settings', 'sales_tax_rate')) {
                $table->decimal('sales_tax_rate', 5, 2)->default(0)->after('logo_path');
            }

            if (!Schema::hasColumn('business_settings', 'service_fee')) {
                $table->decimal('service_fee', 12, 2)->default(0)->after('sales_tax_rate');
            }

            if (!Schema::hasColumn('business_settings', 'fbr_invoice_prefix')) {
                $table->string('fbr_invoice_prefix', 20)->default('FBR')->after('service_fee');
            }

            if (!Schema::hasColumn('business_settings', 'receipt_back_heading')) {
                $table->string('receipt_back_heading')->default('Terms & Policies')->after('fbr_invoice_prefix');
            }

            if (!Schema::hasColumn('business_settings', 'receipt_back_notes')) {
                $table->text('receipt_back_notes')->nullable()->after('receipt_back_heading');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('business_settings')) {
            return;
        }

        Schema::table('business_settings', function (Blueprint $table) {
            foreach ([
                'receipt_back_notes',
                'receipt_back_heading',
                'fbr_invoice_prefix',
                'service_fee',
                'sales_tax_rate',
                'delivery_phone',
            ] as $column) {
                if (Schema::hasColumn('business_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
