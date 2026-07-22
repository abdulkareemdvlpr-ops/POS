<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('held_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('label')->nullable();
            $table->foreignId('cashier_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('customer_id')->nullable()->constrained('customers')->onDelete('set null');
            $table->json('data'); // items, discount, discount_type, tax, service_fee, payment_method, notes
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('held_invoices');
    }
};
