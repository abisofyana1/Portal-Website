<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('news', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('keyword')->nullable();
            $table->string('category')->nullable();
            $table->date('start_time')->nullable();
            $table->date('end_time')->nullable();
            $table->string('headline')->default('No'); // Yes/No
            $table->longText('content')->nullable();
            $table->string('attachment')->nullable();
            $table->string('target_unit')->nullable(); // Stores the Unit Name or ID this news is for
            $table->string('created_by')->nullable(); // Store name for simplicity as per current frontend mock
            $table->string('updated_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('news');
    }
};
