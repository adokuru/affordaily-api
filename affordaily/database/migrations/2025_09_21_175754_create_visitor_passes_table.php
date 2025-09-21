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
        Schema::create('visitor_passes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->onDelete('cascade');
            $table->string('visitor_name');
            $table->string('visitor_phone')->nullable();
            $table->string('visitor_id_photo_path')->nullable();
            $table->datetime('check_in_time');
            $table->datetime('check_out_time')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('issued_by')->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visitor_passes');
    }
};
