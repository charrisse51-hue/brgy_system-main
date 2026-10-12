<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('alerts', function (Blueprint $table) {
            $table->string('reporter_first_name')->nullable()->after('actions_taken');
            $table->string('reporter_middle_name')->nullable()->after('reporter_first_name');
            $table->string('reporter_last_name')->nullable()->after('reporter_middle_name');
            $table->unsignedInteger('reporter_age')->nullable()->after('reporter_last_name');
            $table->string('reporter_sitio')->nullable()->after('reporter_age');
            $table->string('reporter_other_address')->nullable()->after('reporter_sitio');
        });
    }

    public function down(): void
    {
        Schema::table('alerts', function (Blueprint $table) {
            $table->dropColumn([
                'reporter_first_name',
                'reporter_middle_name',
                'reporter_last_name',
                'reporter_age',
                'reporter_sitio',
                'reporter_other_address',
            ]);
        });
    }
};

