<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            // 'tablet' (single unit), 'strip', or 'box' — what the cashier picked at billing time.
            $table->string('unit_type')->default('tablet')->after('product_id');
            // How many of that unit were sold (e.g. 2 boxes). `qty` remains the base-unit
            // (tablet/piece) quantity used for stock deduction and profit math.
            $table->unsignedInteger('unit_qty')->default(1)->after('unit_type');
        });
    }

    public function down(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropColumn(['unit_type', 'unit_qty']);
        });
    }
};
