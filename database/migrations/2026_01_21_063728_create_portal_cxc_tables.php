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
        Schema::create('system_parameter', function (Blueprint $table) {
            $table->id();
            $table->string('key')->nullable();
            $table->string('value')->nullable();
            $table->string('title')->nullable();
            $table->string('url')->nullable();
            $table->timestamps();
        });

        Schema::create('employee', function (Blueprint $table) {
            $table->id();
            $table->string('npp')->nullable();
            $table->string('name')->nullable();
            $table->integer('is_active')->nullable();
            $table->string('email')->nullable();
            $table->timestamp('last_login')->nullable();
            $table->timestamps();
            $table->integer('created_by_id')->nullable();
            $table->integer('updated_by_id')->nullable();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('username')->unique();
            $table->string('password')->nullable();
            $table->foreignId('employee_id')->constrained('employee');
            $table->timestamp('last_login')->nullable();
            $table->integer('attempt_count')->nullable();
            $table->timestamp('last_logout')->nullable();
            $table->timestamps();
            $table->integer('created_by_id')->nullable();
            $table->integer('updated_by_id')->nullable();
        });
        
        Schema::create('unit', function (Blueprint $table) {
            $table->id();
            $table->integer('parent_id')->nullable();
            $table->integer('type')->nullable();
            $table->string('code')->nullable();
            $table->string('short_name')->nullable();
            $table->string('name')->nullable();
            $table->string('address')->nullable();
            $table->integer('is_active')->default(1);
            $table->timestamps();
            $table->integer('created_by_id')->nullable();
            $table->integer('updated_by_id')->nullable();
        });

        Schema::create('employee_role', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employee');
            $table->foreignId('unit_id')->nullable()->constrained('unit');
            $table->integer('role_id');
            $table->integer('status_id')->nullable();
            $table->timestamps();
        });

        Schema::create('lookup', function (Blueprint $table) {
            $table->id();
            $table->string('type')->nullable();
            $table->string('name')->nullable();
            $table->integer('value')->nullable();
            $table->integer('order_no')->nullable();
            $table->timestamps();
            $table->integer('created_by_id')->nullable();
            $table->integer('updated_by_id')->nullable();
        });

        Schema::create('news_bank', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->string('category')->nullable();
            $table->string('keyword')->nullable();
            $table->text('content')->nullable();
            $table->integer('is_active')->nullable();
            $table->string('created_name')->nullable();
            $table->string('updated_name')->nullable();
            $table->integer('category_id')->nullable();
            $table->timestamp('start_time')->nullable();
            $table->timestamp('end_time')->nullable();
            $table->integer('flag')->nullable();
            $table->string('ref_id')->nullable();
            $table->integer('view_count')->nullable();
            $table->timestamps();
            $table->integer('created_by_id')->nullable();
            $table->integer('updated_by_id')->nullable();
        });

        Schema::create('user_session', function (Blueprint $table) {
            $table->string('session_id')->primary();
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->timestamp('last_active')->nullable();
            $table->string('info')->nullable();
            $table->integer('role_id')->nullable();
            $table->integer('unit_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('portal_cxc_tables');
    }
};
