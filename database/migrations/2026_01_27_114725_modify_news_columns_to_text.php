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
        Schema::table('news', function (Blueprint $table) {
            $table->text('title')->change();
            $table->text('target_unit')->nullable()->change();
            $table->text('keyword')->nullable()->change(); // Also widen keyword just in case
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('news', function (Blueprint $table) {
            $table->string('title')->change();
            $table->string('target_unit')->nullable()->change();
            $table->string('keyword')->nullable()->change();
        });
    }
};
