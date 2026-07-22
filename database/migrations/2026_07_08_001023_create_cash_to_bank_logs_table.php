<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('cash_to_bank_logs', function (Blueprint $table) {
            $table->id();
            $table->decimal('amount', 12, 2);
            $table->string('bank_name');
            $table->string('slip_number')->nullable();
            $table->date('deposit_date');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('cash_to_bank_logs'); }
};
