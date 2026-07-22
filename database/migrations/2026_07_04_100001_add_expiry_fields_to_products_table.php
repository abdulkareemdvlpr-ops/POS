<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('generic_name')->nullable()->after('name');
            $table->string('batch_number')->nullable()->after('barcode');
            $table->date('mfg_date')->nullable()->after('batch_number');
            $table->date('expiry_date')->nullable()->after('mfg_date');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['generic_name', 'batch_number', 'mfg_date', 'expiry_date']);
        });
    }
};
