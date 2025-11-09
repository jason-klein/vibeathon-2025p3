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
        Schema::create('patient_appointment_documents', static function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_appointment_id')->constrained()->cascadeOnDelete();
            $table->string('file_path');
            $table->text('summary')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patient_appointment_documents');
    }
};
