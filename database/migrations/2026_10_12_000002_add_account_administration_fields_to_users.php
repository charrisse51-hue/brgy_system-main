<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('first_name', 100)->nullable();
            $table->string('middle_name', 100)->nullable();
            $table->string('last_name', 100)->nullable();
            $table->string('username', 50)->nullable()->unique();
            $table->timestamp('last_login_at')->nullable();
            $table->unsignedInteger('auth_version')->default(0);
            $table->boolean('can_generate_reports')->default(true);
        });
    }
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropColumn(['first_name', 'middle_name', 'last_name', 'username', 'last_login_at', 'auth_version', 'can_generate_reports']);
        });
    }
};
