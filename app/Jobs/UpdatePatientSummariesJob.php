<?php

namespace App\Jobs;

use App\Models\Patient;
use App\Services\AiSummaryService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class UpdatePatientSummariesJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Patient $patient
    ) {}

    /**
     * Execute the job.
     */
    public function handle(AiSummaryService $aiService): void
    {
        try {
            $aiService->updatePatientSummaries($this->patient);
        } catch (\Exception $e) {
            Log::error('Failed to update patient summaries', [
                'patient_id' => $this->patient->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
