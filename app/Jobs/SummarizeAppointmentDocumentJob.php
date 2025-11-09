<?php

namespace App\Jobs;

use App\Models\PatientAppointmentDocument;
use App\Services\AiSummaryService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SummarizeAppointmentDocumentJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public PatientAppointmentDocument $document
    ) {}

    /**
     * Execute the job.
     */
    public function handle(AiSummaryService $aiService): void
    {
        try {
            $summary = $aiService->generateDocumentExecutiveSummary($this->document);

            $this->document->update([
                'summary' => $summary,
            ]);

            // After document summary is generated, trigger appointment summary update
            UpdateAppointmentExecutiveSummaryJob::dispatch($this->document->appointment);
        } catch (\Exception $e) {
            Log::error('Failed to summarize appointment document', [
                'document_id' => $this->document->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
