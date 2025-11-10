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
            $table->foreign('scheduled_from_task_id')
                ->references('id')
                ->on('patient_tasks')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('patient_appointments', function (Blueprint $table) {
            $table->dropForeign(['scheduled_from_task_id']);
        });
    }
};
