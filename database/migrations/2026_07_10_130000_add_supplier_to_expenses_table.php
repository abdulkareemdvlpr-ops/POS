<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            if (!Schema::hasColumn('expenses', 'supplier_id')) {
                $table->foreignId('supplier_id')->nullable()->after('category')->constrained()->onDelete('set null');
            }
            if (!Schema::hasColumn('expenses', 'bill_number')) {
                $table->string('bill_number')->nullable()->after('supplier_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            if (Schema::hasColumn('expenses', 'supplier_id')) {
                $table->dropForeign(['supplier_id']);
                $table->dropColumn('supplier_id');
            }
            if (Schema::hasColumn('expenses', 'bill_number')) {
                $table->dropColumn('bill_number');
            }
        });
    }
};
