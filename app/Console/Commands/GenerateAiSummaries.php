<?php

namespace App\Console\Commands;

use App\Jobs\SummarizeAppointmentDocumentJob;
use App\Jobs\UpdateAppointmentExecutiveSummaryJob;
use App\Jobs\UpdatePatientSummariesJob;
use App\Models\Patient;
use App\Models\PatientAppointment;
use App\Models\PatientAppointmentDocument;
use App\Services\AiSummaryService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class GenerateAiSummaries extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ai:generate-summaries {--Q|queue : Queue the summary generation jobs instead of running synchronously} {--force : Regenerate summaries even if they already exist} {--patient-id= : Only process records for a specific patient ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate AI summaries for patient appointment documents, appointments, and patient records';

    protected array $errors = [];

    public function __construct(protected AiSummaryService $aiSummaryService)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $useQueue = $this->option('queue');
        $force = $this->option('force');
        $patientId = $this->option('patient-id');

        $this->info('Starting AI summary generation...');
        $this->info('Mode: '.($useQueue ? 'Queue' : 'Synchronous'));

        if ($force) {
            $this->warn('Force mode enabled - regenerating all summaries');
        }

        if ($patientId) {
            $this->info("Filtering for patient ID: {$patientId}");
        }

        $this->newLine();

        // Process in order as specified
        $this->processDocumentSummaries($useQueue, $force, $patientId);
        $this->processAppointmentExecutiveSummaries($useQueue, $force, $patientId);
        $this->processPatientSummaries($useQueue, $force, $patientId);

        $this->newLine();
        $this->info('AI summary generation completed!');

        if (count($this->errors) > 0) {
            $this->newLine();
            $this->error('Errors encountered: '.count($this->errors));
            foreach ($this->errors as $error) {
                $this->line("  - {$error}");
            }

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    protected function processDocumentSummaries(bool $useQueue, bool $force, ?string $patientId): void
    {
        $this->info('1. Processing PatientAppointmentDocument.summary...');

        $query = PatientAppointmentDocument::query();

        if (! $force) {
            $query->whereNull('summary');
        }

        if ($patientId) {
            $query->whereHas('appointment', fn ($q) => $q->where('patient_id', $patientId));
        }

        $documents = $query->get();
        $count = $documents->count();

        if ($count === 0) {
            $this->line('  No documents to process');

            return;
        }

        $this->line("  Found {$count} document(s) to process");

        $processed = 0;
        $bar = $this->output->createProgressBar($count);
        $bar->start();

        foreach ($documents as $document) {
            try {
                if ($useQueue) {
                    SummarizeAppointmentDocumentJob::dispatch($document);
                } else {
                    $summary = $this->aiSummaryService->generateDocumentExecutiveSummary($document);
                    $document->update(['summary' => $summary]);
                }
                $processed++;
            } catch (\Exception $e) {
                $this->errors[] = "Document {$document->id}: {$e->getMessage()}";
                Log::error("Failed to generate summary for document {$document->id}", [
                    'error' => $e->getMessage(),
                    'document_id' => $document->id,
                ]);
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->line("  Processed {$processed} of {$count} document(s)");
        $this->newLine();
    }

    protected function processAppointmentExecutiveSummaries(bool $useQueue, bool $force, ?string $patientId): void
    {
        $this->info('2. Processing PatientAppointment.executive_summary...');

        $query = PatientAppointment::query()
            ->with(['documents', 'provider', 'tasks']);

        if (! $force) {
            $query->whereNull('executive_summary');
        }

        if ($patientId) {
            $query->where('patient_id', $patientId);
        }

        $appointments = $query->get();
        $count = $appointments->count();

        if ($count === 0) {
            $this->line('  No appointments to process');

            return;
        }

        $this->line("  Found {$count} appointment(s) to process");

        $processed = 0;
        $bar = $this->output->createProgressBar($count);
        $bar->start();

        foreach ($appointments as $appointment) {
            try {
                if ($useQueue) {
                    UpdateAppointmentExecutiveSummaryJob::dispatch($appointment);
                } else {
                    $summary = $this->aiSummaryService->generateAppointmentExecutiveSummary($appointment);
                    $appointment->update(['executive_summary' => $summary]);
                }
                $processed++;
            } catch (\Exception $e) {
                $this->errors[] = "Appointment {$appointment->id}: {$e->getMessage()}";
                Log::error("Failed to generate summary for appointment {$appointment->id}", [
                    'error' => $e->getMessage(),
                    'appointment_id' => $appointment->id,
                ]);
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->line("  Processed {$processed} of {$count} appointment(s)");
        $this->newLine();
    }

    protected function processPatientSummaries(bool $useQueue, bool $force, ?string $patientId): void
    {
        $this->info('3. Processing Patient.executive_summary and Patient.plain_english_record...');

        $query = Patient::query()
            ->with('appointments');

        if (! $force) {
            $query->where(function ($q) {
                $q->whereNull('executive_summary')
                    ->orWhereNull('plain_english_record');
            });
        }

        if ($patientId) {
            $query->where('id', $patientId);
        }

        $patients = $query->get();
        $count = $patients->count();

        if ($count === 0) {
            $this->line('  No patients to process');

            return;
        }

        $this->line("  Found {$count} patient(s) to process");

        $processed = 0;
        $bar = $this->output->createProgressBar($count);
        $bar->start();

        foreach ($patients as $patient) {
            try {
                if ($useQueue) {
                    UpdatePatientSummariesJob::dispatch($patient);
                } else {
                    $this->aiSummaryService->updatePatientSummaries($patient);
                }
                $processed++;
            } catch (\Exception $e) {
                $this->errors[] = "Patient {$patient->id}: {$e->getMessage()}";
                Log::error("Failed to generate summaries for patient {$patient->id}", [
                    'error' => $e->getMessage(),
                    'patient_id' => $patient->id,
                ]);
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->line("  Processed {$processed} of {$count} patient(s)");
        $this->newLine();
    }
}
