<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_session', function (Blueprint $table) {
            $table->renameColumn('session_id', 'id');
            $table->dropColumn('last_active');
            $table->longText('payload')->nullable();
            $table->integer('last_activity')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('user_session', function (Blueprint $table) {
            $table->dropColumn(['payload', 'last_activity', 'ip_address', 'user_agent']);
            $table->timestamp('last_active')->nullable();
            $table->renameColumn('id', 'session_id');
        });
    }
};
