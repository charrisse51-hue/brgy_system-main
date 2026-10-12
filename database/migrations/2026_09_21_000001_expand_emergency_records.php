<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void
    {
        Schema::table('alerts', function (Blueprint $table) {
            $table->boolean('is_record')->default(false)->index();
            $table->string('report_source')->nullable();
            $table->dateTime('report_date_time')->nullable();
            $table->string('report_type')->nullable();
            $table->string('progress_report_no')->nullable();
            $table->string('emergency_type')->nullable();
            $table->string('location')->nullable();
            $table->date('incident_date')->nullable();
            $table->time('incident_time')->nullable();
            $table->text('actions_taken')->nullable();
            $table->string('reporter_name')->nullable();
            $table->string('reporter_contact')->nullable();
            $table->string('reporter_address')->nullable();
            $table->string('reporter_relationship')->nullable();
            $table->string('released_by')->nullable();
            $table->dateTime('date_verified')->nullable();
            $table->string('verification_status')->nullable();
            $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('checked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
        });
        Schema::create('emergency_casualties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('emergency_record_id')->constrained('alerts')->cascadeOnDelete();
            $table->string('casualty_type')->nullable();
            $table->string('name')->nullable();
            $table->unsignedInteger('age')->nullable();
            $table->string('sex')->nullable();
            $table->string('address')->nullable();
            $table->string('cause')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
        Schema::create('emergency_related_incidents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('emergency_record_id')->constrained('alerts')->cascadeOnDelete();
            $table->string('site')->nullable();
            $table->string('type')->nullable();
            $table->dateTime('occurred_at')->nullable();
            $table->text('description')->nullable();
            $table->text('actions_taken')->nullable();
            $table->text('remarks')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
        });
        Schema::create('emergency_affected_population', function (Blueprint $table) {
            $table->id();
            $table->foreignId('emergency_record_id')->constrained('alerts')->cascadeOnDelete();
            $table->string('site')->nullable();
            $table->unsignedInteger('families')->nullable();
            $table->unsignedInteger('persons')->nullable();
            $table->unsignedInteger('rescued')->nullable();
            $table->timestamps();
        });
        Schema::create('emergency_roads_bridges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('emergency_record_id')->constrained('alerts')->cascadeOnDelete();
            $table->string('site')->nullable();
            $table->string('type')->nullable();
            $table->string('name')->nullable();
            $table->string('condition')->nullable();
            $table->dateTime('reported_at')->nullable();
            $table->dateTime('restored_at')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
        Schema::create('emergency_power_outages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('emergency_record_id')->constrained('alerts')->cascadeOnDelete();
            $table->string('site')->nullable();
            $table->string('type')->nullable();
            $table->string('provider')->nullable();
            $table->dateTime('interrupted_at')->nullable();
            $table->dateTime('restored_at')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
        Schema::create('emergency_water_supply', function (Blueprint $table) {
            $table->id();
            $table->foreignId('emergency_record_id')->constrained('alerts')->cascadeOnDelete();
            $table->string('site')->nullable();
            $table->string('type')->nullable();
            $table->string('provider')->nullable();
            $table->dateTime('interrupted_at')->nullable();
            $table->dateTime('restored_at')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
        Schema::create('emergency_communication_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('emergency_record_id')->constrained('alerts')->cascadeOnDelete();
            $table->string('site')->nullable();
            $table->string('telecompany')->nullable();
            $table->dateTime('interrupted_at')->nullable();
            $table->dateTime('restored_at')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
        Schema::create('emergency_damaged_houses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('emergency_record_id')->constrained('alerts')->cascadeOnDelete();
            $table->string('site')->nullable();
            $table->unsignedInteger('totally_damaged')->nullable();
            $table->unsignedInteger('partially_damaged')->nullable();
            $table->unsignedInteger('total')->nullable();
            $table->decimal('cost', 16, 2)->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
        Schema::create('emergency_agriculture_damage', function (Blueprint $table) {
            $table->id();
            $table->foreignId('emergency_record_id')->constrained('alerts')->cascadeOnDelete();
            $table->string('site')->nullable();
            $table->string('classification')->nullable();
            $table->string('commodity')->nullable();
            $table->unsignedInteger('farmers')->nullable();
            $table->decimal('area', 16, 2)->nullable();
            $table->string('area_unit')->nullable();
            $table->decimal('totally_damaged', 16, 2)->nullable();
            $table->decimal('partially_damaged', 16, 2)->nullable();
            $table->decimal('cost', 16, 2)->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
        Schema::create('emergency_infrastructure_damage', function (Blueprint $table) {
            $table->id();
            $table->foreignId('emergency_record_id')->constrained('alerts')->cascadeOnDelete();
            $table->string('site')->nullable();
            $table->string('type')->nullable();
            $table->string('classification')->nullable();
            $table->string('name')->nullable();
            $table->unsignedInteger('number_damaged')->nullable();
            $table->string('unit')->nullable();
            $table->decimal('quantity', 16, 2)->nullable();
            $table->string('status')->nullable();
            $table->decimal('cost', 16, 2)->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
        Schema::create('emergency_damaged_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('emergency_record_id')->constrained('alerts')->cascadeOnDelete();
            $table->string('site')->nullable();
            $table->string('classification')->nullable();
            $table->string('name')->nullable();
            $table->string('unit')->nullable();
            $table->decimal('quantity', 16, 2)->nullable();
            $table->decimal('cost', 16, 2)->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('emergency_damaged_assets');
        Schema::dropIfExists('emergency_infrastructure_damage');
        Schema::dropIfExists('emergency_agriculture_damage');
        Schema::dropIfExists('emergency_damaged_houses');
        Schema::dropIfExists('emergency_communication_lines');
        Schema::dropIfExists('emergency_water_supply');
        Schema::dropIfExists('emergency_power_outages');
        Schema::dropIfExists('emergency_roads_bridges');
        Schema::dropIfExists('emergency_affected_population');
        Schema::dropIfExists('emergency_related_incidents');
        Schema::dropIfExists('emergency_casualties');
        Schema::table('alerts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('validated_by');
            $table->dropConstrainedForeignId('checked_by');
            $table->dropConstrainedForeignId('verified_by');
            $table->dropColumn(['is_record', 'report_source', 'report_date_time', 'report_type', 'progress_report_no', 'emergency_type', 'location', 'incident_date', 'incident_time', 'actions_taken', 'reporter_name', 'reporter_contact', 'reporter_address', 'reporter_relationship', 'released_by', 'date_verified', 'verification_status']);
        });
    }
};

