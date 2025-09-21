<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->onDelete('cascade');
            $table->enum('payment_method', ['cash', 'transfer']);
            $table->decimal('amount', 10, 2);
            $table->string('payer_name');
            $table->string('reference')->nullable();
            $table->boolean('is_confirmed')->default(false);
            $table->datetime('confirmed_at')->nullable();
            $table->foreignId('processed_by')->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
