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
        Schema::create('patient_appointments', static function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('healthcare_provider_id')->nullable()->after('patient_id')->constrained()->nullOnDelete();
            $table->date('date');
            $table->time('time')->nullable();
            $table->string('location')->nullable();
            $table->text('summary')->nullable();
            $table->text('patient_notes')->nullable();
            $table->foreignId('scheduled_from_task_id')->nullable()->constrained('patient_tasks')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('patient_appointments', function (Blueprint $table) {
            $table->dropForeign(['healthcare_provider_id']);
            $table->dropColumn('healthcare_provider_id');
        });
        Schema::dropIfExists('patient_appointments');
    }
};
