<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('emergency_casualties', function (Blueprint $table) {
            $table->string('first_name', 50)->nullable();
            $table->string('middle_name', 50)->nullable();
            $table->string('last_name', 50)->nullable();
        });
    }
    public function down(): void
    {
        Schema::table('emergency_casualties', fn (Blueprint $table) => $table->dropColumn(['first_name','middle_name','last_name']));
    }
};
