<?php

declare(strict_types=1);

use App\Jobs\SummarizeAppointmentDocumentJob;
use App\Jobs\UpdateAppointmentExecutiveSummaryJob;
use App\Jobs\UpdatePatientSummariesJob;
use App\Models\Patient;
use App\Models\PatientAppointment;
use App\Models\PatientAppointmentDocument;
use App\Models\User;
use App\Services\AiSummaryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\artisan;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
});

test('command processes documents, appointments, and patients synchronously by default', function () {
    $user = User::factory()->create();
    $patient = $user->patient;
    $patient->update([
        'executive_summary' => null,
        'plain_english_record' => null,
    ]);

    $appointment = PatientAppointment::factory()
        ->for($patient)
        ->create(['date' => now()->subDay()]);

    $document = PatientAppointmentDocument::withoutEvents(function () use ($appointment) {
        return PatientAppointmentDocument::factory()
            ->for($appointment, 'appointment')
            ->create([
                'file_path' => 'appointment_docs/test.pdf',
                'summary' => null,
            ]);
    });

    $aiService = $this->mock(AiSummaryService::class);

    // Expect document summary generation (section 1)
    $aiService->shouldReceive('generateDocumentExecutiveSummary')
        ->once()
        ->with(\Mockery::on(fn ($doc) => $doc->id === $document->id))
        ->andReturn('Document summary');

    // Expect appointment summary generation (section 2)
    $aiService->shouldReceive('generateAppointmentExecutiveSummary')
        ->once()
        ->with(\Mockery::on(fn ($appt) => $appt->id === $appointment->id))
        ->andReturn('Appointment summary');

    // Expect patient summaries update (section 3)
    $aiService->shouldReceive('updatePatientSummaries')
        ->once()
        ->with(\Mockery::on(fn ($pat) => $pat->id === $patient->id));

    artisan('ai:generate-summaries')
        ->expectsOutput('Mode: Synchronous')
        ->assertSuccessful();

    expect($document->fresh()->summary)->toBe('Document summary');
    expect($appointment->fresh()->executive_summary)->toBe('Appointment summary');
});

test('command queues jobs when --queue flag is provided', function () {
    Queue::fake();

    $user = User::factory()->create();
    $patient = $user->patient;
    $patient->update([
        'executive_summary' => null,
        'plain_english_record' => null,
    ]);
    $appointment = PatientAppointment::factory()
        ->for($patient)
        ->create();

    $document = PatientAppointmentDocument::withoutEvents(function () use ($appointment) {
        return PatientAppointmentDocument::factory()
            ->for($appointment, 'appointment')
            ->create([
                'file_path' => 'appointment_docs/test.pdf',
                'summary' => null,
            ]);
    });

    artisan('ai:generate-summaries --queue')
        ->expectsOutput('Mode: Queue')
        ->assertSuccessful();

    Queue::assertPushed(SummarizeAppointmentDocumentJob::class);
    Queue::assertPushed(UpdateAppointmentExecutiveSummaryJob::class);
    Queue::assertPushed(UpdatePatientSummariesJob::class);
});

test('command can use -Q shortcut for queue flag', function () {
    Queue::fake();

    $user = User::factory()->create();
    $patient = Patient::factory()->for($user)->create();

    artisan('ai:generate-summaries -Q')
        ->expectsOutput('Mode: Queue')
        ->assertSuccessful();
});

test('command processes only records with null summaries by default', function () {
    $user = User::factory()->create();
    // User factory auto-creates a patient, so let's update it with existing summaries
    $patient = $user->patient;
    $patient->update([
        'executive_summary' => 'Existing summary',
        'plain_english_record' => 'Existing record',
    ]);

    $appointment = PatientAppointment::factory()
        ->for($patient)
        ->create(['executive_summary' => 'Existing summary']);

    PatientAppointmentDocument::withoutEvents(function () use ($appointment) {
        PatientAppointmentDocument::factory()
            ->for($appointment, 'appointment')
            ->create([
                'file_path' => 'appointment_docs/test.pdf',
                'summary' => 'Existing summary',
            ]);
    });

    // Verify our setup is correct
    expect(PatientAppointmentDocument::whereNull('summary')->count())->toBe(0);
    expect(PatientAppointment::whereNull('executive_summary')->count())->toBe(0);
    expect(Patient::whereNull('executive_summary')->orWhereNull('plain_english_record')->count())->toBe(0);

    $aiService = $this->mock(AiSummaryService::class);
    $aiService->shouldNotReceive('generateDocumentExecutiveSummary');
    $aiService->shouldNotReceive('generateAppointmentExecutiveSummary');
    $aiService->shouldNotReceive('updatePatientSummaries');

    $result = artisan('ai:generate-summaries');

    // Check the output - using contains since there may be leading spaces
    $result->expectsOutputToContain('No documents to process')
        ->expectsOutputToContain('No appointments to process')
        ->expectsOutputToContain('No patients to process')
        ->assertSuccessful();
});

test('command regenerates all summaries when --force flag is provided', function () {
    $user = User::factory()->create();
    $patient = $user->patient;
    $patient->update([
        'executive_summary' => 'Existing summary',
        'plain_english_record' => 'Existing record',
    ]);

    $appointment = PatientAppointment::factory()
        ->for($patient)
        ->create([
            'date' => now()->subDay(),
            'executive_summary' => 'Existing summary',
        ]);

    $document = PatientAppointmentDocument::withoutEvents(function () use ($appointment) {
        return PatientAppointmentDocument::factory()
            ->for($appointment, 'appointment')
            ->create([
                'file_path' => 'appointment_docs/test.pdf',
                'summary' => 'Existing summary',
            ]);
    });

    $aiService = $this->mock(AiSummaryService::class);

    $aiService->shouldReceive('generateDocumentExecutiveSummary')->once()->andReturn('New summary');
    $aiService->shouldReceive('generateAppointmentExecutiveSummary')->once()->andReturn('New summary');
    $aiService->shouldReceive('updatePatientSummaries')->once();

    artisan('ai:generate-summaries --force')
        ->expectsOutput('Force mode enabled - regenerating all summaries')
        ->assertSuccessful();
});

test('command filters by patient ID when --patient-id is provided', function () {
    $user1 = User::factory()->create();
    $patient1 = $user1->patient;
    $patient1->update([
        'executive_summary' => null,
        'plain_english_record' => null,
    ]);
    $appointment1 = PatientAppointment::factory()->for($patient1)->create(['date' => now()->subDay()]);
    $document1 = PatientAppointmentDocument::withoutEvents(function () use ($appointment1) {
        return PatientAppointmentDocument::factory()
            ->for($appointment1, 'appointment')
            ->create(['file_path' => 'docs/test1.pdf']);
    });

    $user2 = User::factory()->create();
    $patient2 = $user2->patient;
    $appointment2 = PatientAppointment::factory()->for($patient2)->create();
    $document2 = PatientAppointmentDocument::withoutEvents(function () use ($appointment2) {
        return PatientAppointmentDocument::factory()
            ->for($appointment2, 'appointment')
            ->create(['file_path' => 'docs/test2.pdf']);
    });

    $aiService = $this->mock(AiSummaryService::class);

    // Should only process patient1's records
    $aiService->shouldReceive('generateDocumentExecutiveSummary')
        ->once()
        ->with(\Mockery::on(fn ($doc) => $doc->id === $document1->id))
        ->andReturn('Summary');

    $aiService->shouldReceive('generateAppointmentExecutiveSummary')
        ->once()
        ->with(\Mockery::on(fn ($appt) => $appt->id === $appointment1->id))
        ->andReturn('Summary');

    $aiService->shouldReceive('updatePatientSummaries')
        ->once()
        ->with(\Mockery::on(fn ($pat) => $pat->id === $patient1->id));

    artisan("ai:generate-summaries --patient-id={$patient1->id}")
        ->expectsOutput("Filtering for patient ID: {$patient1->id}")
        ->assertSuccessful();
});

test('command processes tables in correct order', function () {
    $user = User::factory()->create();
    $patient = $user->patient;
    $patient->update([
        'executive_summary' => null,
        'plain_english_record' => null,
    ]);
    $appointment = PatientAppointment::factory()->for($patient)->create(['date' => now()->subDay()]);
    $document = PatientAppointmentDocument::withoutEvents(function () use ($appointment) {
        return PatientAppointmentDocument::factory()
            ->for($appointment, 'appointment')
            ->create(['file_path' => 'docs/test.pdf']);
    });

    $processingOrder = [];

    $aiService = $this->mock(AiSummaryService::class);

    $aiService->shouldReceive('generateDocumentExecutiveSummary')
        ->once()
        ->andReturnUsing(function () use (&$processingOrder) {
            $processingOrder[] = 'document';

            return 'Document summary';
        });

    $aiService->shouldReceive('generateAppointmentExecutiveSummary')
        ->once()
        ->andReturnUsing(function () use (&$processingOrder) {
            $processingOrder[] = 'appointment';

            return 'Appointment summary';
        });

    $aiService->shouldReceive('updatePatientSummaries')
        ->once()
        ->andReturnUsing(function () use (&$processingOrder) {
            $processingOrder[] = 'patient';
        });

    artisan('ai:generate-summaries')->assertSuccessful();

    // Verify order: documents -> appointments -> patients
    expect($processingOrder)->toBe(['document', 'appointment', 'patient']);
});

test('command continues processing on errors and reports them', function () {
    $user = User::factory()->create();
    $patient = $user->patient;
    $patient->update([
        'executive_summary' => null,
        'plain_english_record' => null,
    ]);
    $appointment = PatientAppointment::factory()->for($patient)->create();

    $document1 = PatientAppointmentDocument::withoutEvents(function () use ($appointment) {
        return PatientAppointmentDocument::factory()
            ->for($appointment, 'appointment')
            ->create(['file_path' => 'docs/test1.pdf']);
    });

    $document2 = PatientAppointmentDocument::withoutEvents(function () use ($appointment) {
        return PatientAppointmentDocument::factory()
            ->for($appointment, 'appointment')
            ->create(['file_path' => 'docs/test2.pdf']);
    });

    $aiService = $this->mock(AiSummaryService::class);

    // First document fails
    $aiService->shouldReceive('generateDocumentExecutiveSummary')
        ->once()
        ->with(\Mockery::on(fn ($doc) => $doc->id === $document1->id))
        ->andThrow(new Exception('OpenAI API error'));

    // Second document succeeds
    $aiService->shouldReceive('generateDocumentExecutiveSummary')
        ->once()
        ->with(\Mockery::on(fn ($doc) => $doc->id === $document2->id))
        ->andReturn('Document 2 summary');

    // These should still be called despite document error
    $aiService->shouldReceive('generateAppointmentExecutiveSummary')->once()->andReturn('Summary');
    $aiService->shouldReceive('updatePatientSummaries')->once()->andReturn();

    artisan('ai:generate-summaries')
        ->expectsOutputToContain('Errors encountered: 1')
        ->assertFailed();

    // Document 2 should have been processed successfully
    expect($document2->fresh()->summary)->toBe('Document 2 summary');
});

test('command shows progress for each table', function () {
    $user = User::factory()->create();

    artisan('ai:generate-summaries')
        ->expectsOutput('1. Processing PatientAppointmentDocument.summary...')
        ->expectsOutput('2. Processing PatientAppointment.executive_summary...')
        ->expectsOutput('3. Processing Patient.executive_summary and Patient.plain_english_record...')
        ->assertSuccessful();
});
