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
        Schema::create('guests', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone')->unique();
            $table->string('email')->nullable();
            $table->string('id_photo_path')->nullable();
            $table->text('notes')->nullable();
            $table->integer('total_stays')->default(0);
            $table->decimal('total_spent', 10, 2)->default(0);
            $table->datetime('last_stay')->nullable();
            $table->boolean('is_blacklisted')->default(false);
            $table->text('blacklist_reason')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guests');
    }
};
