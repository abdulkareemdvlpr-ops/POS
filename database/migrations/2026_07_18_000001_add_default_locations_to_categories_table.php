<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->string('default_almari')->nullable()->after('product_type');
            $table->string('default_khana')->nullable()->after('default_almari');
            $table->string('default_row')->nullable()->after('default_khana');
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn(['default_almari', 'default_khana', 'default_row']);
        });
    }
};
