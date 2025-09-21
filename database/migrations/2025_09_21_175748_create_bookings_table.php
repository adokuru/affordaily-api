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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained()->onDelete('cascade');
            $table->string('guest_name');
            $table->string('guest_phone');
            $table->string('id_photo_path')->nullable();
            $table->datetime('check_in_time');
            $table->datetime('check_out_time');
            $table->datetime('scheduled_checkout_time');
            $table->integer('number_of_nights');
            $table->enum('status', ['active', 'completed', 'pending_checkout', 'auto_checkout', 'early_checkout'])->default('active');
            $table->decimal('total_amount', 10, 2);
            $table->decimal('amount_paid', 10, 2)->default(0);
            $table->text('damage_notes')->nullable();
            $table->boolean('key_returned')->default(false);
            $table->datetime('auto_checkout_time')->nullable();
            $table->string('auto_checkout_reason')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
