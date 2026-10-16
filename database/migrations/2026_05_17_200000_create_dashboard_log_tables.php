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
        // Table for tracking user logins by unit
        Schema::create('user_login_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->unsignedBigInteger('unit_id')->nullable();
            $table->string('unit_name')->nullable(); // Helper to query by name directly
            $table->timestamp('login_at');
            $table->timestamps();
        });

        // Table for tracking menu accesses from Menu 1 to 15
        Schema::create('menu_access_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->unsignedBigInteger('unit_id')->nullable();
            $table->string('unit_name')->nullable(); // Helper to query by name directly
            $table->string('menu_name'); // 'Menu 1' through 'Menu 15'
            $table->string('menu_title')->nullable();
            $table->timestamp('accessed_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('menu_access_logs');
        Schema::dropIfExists('user_login_logs');
    }
};
