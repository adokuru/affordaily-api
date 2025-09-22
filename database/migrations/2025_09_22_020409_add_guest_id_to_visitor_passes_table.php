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
        Schema::table('visitor_passes', function (Blueprint $table) {
            $table->foreignId('guest_id')->nullable()->constrained()->after('booking_id');
            // Remove visitor-specific fields since we'll use guest data
            $table->dropColumn(['visitor_name', 'visitor_phone', 'visitor_id_photo_path']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('visitor_passes', function (Blueprint $table) {
            $table->dropForeign(['guest_id']);
            $table->dropColumn('guest_id');
            // Restore visitor-specific fields
            $table->string('visitor_name');
            $table->string('visitor_phone')->nullable();
            $table->string('visitor_id_photo_path')->nullable();
        });
    }
};
