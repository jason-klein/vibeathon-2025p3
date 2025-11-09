<?php

namespace App\Jobs;

use App\Models\PatientAppointment;
use App\Services\AiSummaryService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class UpdateAppointmentExecutiveSummaryJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public PatientAppointment $appointment
    ) {}

    /**
     * Execute the job.
     */
    public function handle(AiSummaryService $aiService): void
    {
        try {
            $summary = $aiService->generateAppointmentExecutiveSummary($this->appointment);

            $this->appointment->update([
                'executive_summary' => $summary,
            ]);

            // If this is a past appointment, trigger patient summaries update
            if ($this->appointment->date->isPast()) {
                UpdatePatientSummariesJob::dispatch($this->appointment->patient);
            }
        } catch (\Exception $e) {
            Log::error('Failed to update appointment executive summary', [
                'appointment_id' => $this->appointment->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
