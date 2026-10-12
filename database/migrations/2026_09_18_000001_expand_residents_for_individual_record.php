<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    private const STRINGS = ['barangay','sitio_purok','house_number','street','subdivision','philsys_card_number','birthplace','sex','religion','citizenship','ethnicity','educational_attainment','school_location','labor_force_status','occupation','pwd_classification','pwd_other','immunization_status','senior_citizen_number','email','blood_type','voter_location','mother_last_name','mother_first_name','mother_middle_name'];

    public function up(): void
    {
        Schema::table('residents', function (Blueprint $table) {
            foreach (self::STRINGS as $column) $table->string($column)->nullable();
            $table->json('income_sources')->nullable();
            $table->json('target_groups')->nullable();
            $table->decimal('monthly_income', 12, 2)->nullable();
            $table->decimal('height_cm', 5, 1)->nullable();
            $table->decimal('weight_kg', 5, 1)->nullable();
            $table->boolean('out_of_school_youth')->nullable();
            $table->boolean('can_read_write')->nullable();
            $table->boolean('registered_voter')->nullable();
            $table->unsignedSmallInteger('last_year_voted')->nullable();
            // Expand the old four-value enum without rewriting existing resident rows.
            $table->string('civil_status')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('residents', function (Blueprint $table) {
            $table->dropColumn(array_merge(self::STRINGS, ['income_sources','target_groups','monthly_income','height_cm','weight_kg','out_of_school_youth','can_read_write','registered_voter','last_year_voted']));
            // Keep civil_status as a string so newer values are not lost on rollback.
        });
    }
};
