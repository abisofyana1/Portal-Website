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
        // Table for logging user activities
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->string('username')->nullable();
            $table->string('action'); // e.g., LOGIN, LOGOUT, CREATE, UPDATE, DELETE, UPLOAD
            $table->string('module'); // e.g., AUTH, USER, UNIT, NEWS, PORTAL_SETTING
            $table->text('description');
            $table->json('payload')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
        });

        // Table for logging system errors
        Schema::create('system_error_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->string('username')->nullable();
            $table->string('error_class'); // e.g., QueryException, Exception
            $table->text('error_message');
            $table->string('file')->nullable();
            $table->integer('line')->nullable();
            $table->longText('trace')->nullable();
            $table->text('explanation'); // Detailed user-friendly explanation
            $table->text('suggestion'); // Actionable suggestions to fix
            $table->string('url')->nullable();
            $table->string('method', 8)->nullable();
            $table->string('ip_address')->nullable();
            $table->string('status')->default('Error');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_error_logs');
        Schema::dropIfExists('activity_logs');
    }
};
