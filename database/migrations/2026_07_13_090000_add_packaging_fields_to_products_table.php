<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // 'medicine' items are sold by Box / Strip / Tablet; 'general' items are always 1:1.
            $table->string('product_type')->default('medicine')->after('category_id');
            // How many strips are in one box, and how many tablets/pieces are in one strip.
            // buy_price / price already store the SINGLE UNIT (loose tablet/piece) price —
            // strip and box prices are calculated on the fly as price * these multipliers.
            $table->unsignedInteger('strips_per_box')->default(1)->after('unit');
            $table->unsignedInteger('tablets_per_strip')->default(1)->after('strips_per_box');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['product_type', 'strips_per_box', 'tablets_per_strip']);
        });
    }
};
