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
        Schema::table('patient_appointments', function (Blueprint $table) {
            $table->text('executive_summary')->nullable()->after('patient_notes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('patient_appointments', function (Blueprint $table) {
            $table->dropColumn('executive_summary');
        });
    }
};
