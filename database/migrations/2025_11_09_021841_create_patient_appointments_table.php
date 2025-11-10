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
            $table->foreignId('healthcare_provider_id')->nullable()->constrained()->nullOnDelete();
            $table->date('date');
            $table->time('time')->nullable();
            $table->string('location')->nullable();
            $table->text('summary')->nullable();
            $table->text('patient_notes')->nullable();
            $table->text('executive_summary')->nullable();
            $table->unsignedBigInteger('scheduled_from_task_id')->nullable();
            $table->string('confirmation_number')->nullable()->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patient_appointments');
    }
};
