<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('invoices', 'paid_amount')) {
                $table->decimal('paid_amount', 12, 2)->default(0)->after('total');
            }
            if (!Schema::hasColumn('invoices', 'due_amount')) {
                $table->decimal('due_amount', 12, 2)->default(0)->after('paid_amount');
            }
        });
        Schema::table('business_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('business_settings', 'cash_in_hand')) {
                $table->decimal('cash_in_hand', 15, 2)->default(0)->after('id');
            }
            if (!Schema::hasColumn('business_settings', 'bank_balance')) {
                $table->decimal('bank_balance', 15, 2)->default(0)->after('cash_in_hand');
            }
        });
    }
    public function down(): void {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['paid_amount', 'due_amount']);
        });
        Schema::table('business_settings', function (Blueprint $table) {
            $table->dropColumn(['cash_in_hand', 'bank_balance']);
        });
    }
};
