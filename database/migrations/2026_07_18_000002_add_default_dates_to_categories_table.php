<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->date('default_mfg_date')->nullable()->after('default_row');
            $table->date('default_expiry_date')->nullable()->after('default_mfg_date');
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn(['default_mfg_date', 'default_expiry_date']);
        });
    }
};
