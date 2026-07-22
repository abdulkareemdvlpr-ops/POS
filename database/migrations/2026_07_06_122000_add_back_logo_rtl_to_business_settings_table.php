<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('business_settings', function (Blueprint $table) {
            $table->string('back_logo_path')->nullable()->after('logo_path');
            $table->boolean('receipt_back_rtl')->default(false)->after('receipt_back_notes');
        });
    }

    public function down(): void
    {
        Schema::table('business_settings', function (Blueprint $table) {
            $table->dropColumn(['back_logo_path', 'receipt_back_rtl']);
        });
    }
};
