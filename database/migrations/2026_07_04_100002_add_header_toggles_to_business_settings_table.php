<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('business_settings', function (Blueprint $table) {
            $table->string('license_no')->nullable()->after('fbr_invoice_prefix');
            $table->string('ntn_no')->nullable()->after('license_no');
            $table->string('strn_no')->nullable()->after('ntn_no');
            $table->boolean('show_license')->default(false)->after('strn_no');
            $table->boolean('show_ntn')->default(false)->after('show_license');
            $table->boolean('show_strn')->default(false)->after('show_ntn');
            $table->boolean('show_phone_on_print')->default(true)->after('show_strn');
        });
    }

    public function down(): void
    {
        Schema::table('business_settings', function (Blueprint $table) {
            $table->dropColumn(['license_no','ntn_no','strn_no','show_license','show_ntn','show_strn','show_phone_on_print']);
        });
    }
};
