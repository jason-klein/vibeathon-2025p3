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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
});

test('document observer dispatches summarize job when document is created', function () {
    Queue::fake();

    $user = User::factory()->create();
    $patient = Patient::factory()->for($user)->create();
    $appointment = PatientAppointment::factory()->for($patient)->create();

    $document = PatientAppointmentDocument::factory()
        ->for($appointment, 'appointment')
        ->create(['file_path' => 'appointment_docs/test.pdf']);

    Queue::assertPushed(SummarizeAppointmentDocumentJob::class, function ($job) use ($document) {
        return $job->document->id === $document->id;
    });
});

test('appointment observer dispatches update job when appointment is updated', function () {
    Queue::fake();

    $user = User::factory()->create();
    $patient = Patient::factory()->for($user)->create();
    $appointment = PatientAppointment::factory()->for($patient)->create();

    // Update a relevant field
    $appointment->update(['summary' => 'Updated summary']);

    Queue::assertPushed(UpdateAppointmentExecutiveSummaryJob::class, function ($job) use ($appointment) {
        return $job->appointment->id === $appointment->id;
    });
});

test('appointment observer does not dispatch job when irrelevant fields are updated', function () {
    Queue::fake();

    $user = User::factory()->create();
    $patient = Patient::factory()->for($user)->create();
    $appointment = PatientAppointment::factory()->for($patient)->create();

    // Update an irrelevant field
    $appointment->update(['created_at' => now()]);

    Queue::assertNotPushed(UpdateAppointmentExecutiveSummaryJob::class);
});

test('update appointment executive summary job triggers patient summaries job for past appointments', function () {
    Queue::fake();

    $user = User::factory()->create();
    $patient = Patient::factory()->for($user)->create();
    $appointment = PatientAppointment::factory()
        ->for($patient)
        ->create(['date' => now()->subDay()]);

    $job = new UpdateAppointmentExecutiveSummaryJob($appointment);
    $aiService = $this->mock(AiSummaryService::class);
    $aiService->shouldReceive('generateAppointmentExecutiveSummary')
        ->once()
        ->with($appointment)
        ->andReturn('Test appointment summary');

    $job->handle($aiService);

    expect($appointment->fresh()->executive_summary)->toBe('Test appointment summary');

    Queue::assertPushed(UpdatePatientSummariesJob::class, function ($job) use ($patient) {
        return $job->patient->id === $patient->id;
    });
});

test('update appointment executive summary job does not trigger patient summaries for future appointments', function () {
    Queue::fake();

    $user = User::factory()->create();
    $patient = Patient::factory()->for($user)->create();
    $appointment = PatientAppointment::factory()
        ->for($patient)
        ->create(['date' => now()->addDay()]);

    $job = new UpdateAppointmentExecutiveSummaryJob($appointment);
    $aiService = $this->mock(AiSummaryService::class);
    $aiService->shouldReceive('generateAppointmentExecutiveSummary')
        ->once()
        ->with($appointment)
        ->andReturn('Test appointment summary');

    $job->handle($aiService);

    Queue::assertNotPushed(UpdatePatientSummariesJob::class);
});

test('summarize document job updates document summary and triggers appointment summary', function () {
    Queue::fake();

    $user = User::factory()->create();
    $patient = Patient::factory()->for($user)->create();
    $appointment = PatientAppointment::factory()->for($patient)->create();
    $document = PatientAppointmentDocument::factory()
        ->for($appointment, 'appointment')
        ->create(['file_path' => 'appointment_docs/test.pdf']);

    $job = new SummarizeAppointmentDocumentJob($document);
    $aiService = $this->mock(AiSummaryService::class);
    $aiService->shouldReceive('generateDocumentExecutiveSummary')
        ->once()
        ->with($document)
        ->andReturn('Test document summary');

    $job->handle($aiService);

    expect($document->fresh()->summary)->toBe('Test document summary');

    Queue::assertPushed(UpdateAppointmentExecutiveSummaryJob::class, function ($job) use ($appointment) {
        return $job->appointment->id === $appointment->id;
    });
});

test('appointment show page displays document summaries', function () {
    $user = User::factory()->create();
    $patient = $user->patient;
    $appointment = PatientAppointment::factory()->for($patient)->create();

    // Disable observers to prevent job dispatching during test
    PatientAppointmentDocument::withoutEvents(function () use ($appointment) {
        return PatientAppointmentDocument::factory()
            ->for($appointment, 'appointment')
            ->create([
                'file_path' => 'appointment_docs/test-document.pdf',
                'summary' => 'This is a detailed AI-generated summary of the document',
            ]);
    });

    $response = $this->actingAs($user)->get(route('appointments.show', $appointment));

    $response->assertOk();
    $response->assertSee('test-document.pdf', false);
    $response->assertSee('This is a detailed AI-generated summary of the document', false);
    $response->assertSee('AI-Generated Summary', false);
});

test('appointment show page shows generating message when document summary is null', function () {
    $user = User::factory()->create();
    $patient = $user->patient;
    $appointment = PatientAppointment::factory()->for($patient)->create();

    // Disable observers to prevent job dispatching during test
    PatientAppointmentDocument::withoutEvents(function () use ($appointment) {
        return PatientAppointmentDocument::factory()
            ->for($appointment, 'appointment')
            ->create([
                'file_path' => 'appointment_docs/test-nodoc.pdf',
                'summary' => null,
            ]);
    });

    $response = $this->actingAs($user)->get(route('appointments.show', $appointment));

    $response->assertOk();
    $response->assertSee('test-nodoc.pdf', false);
    $response->assertSee('Summary generating...', false);
});

test('timeline page displays appointment executive summaries', function () {
    $user = User::factory()->create();
    $patient = $user->patient;
    $appointment = PatientAppointment::factory()
        ->for($patient)
        ->create([
            'date' => now()->subDay(),
            'executive_summary' => 'This is a comprehensive AI-generated appointment summary',
        ]);

    $response = $this->actingAs($user)->get(route('timeline'));

    $response->assertOk();
    $response->assertSee('This is a comprehensive AI-generated appointment summary', false);
    $response->assertSee('Appointment Summary', false);
    $response->assertSee('AI-Generated', false);
});

test('timeline page shows generating message when appointment executive summary is null', function () {
    $user = User::factory()->create();
    $patient = $user->patient;
    $appointment = PatientAppointment::factory()
        ->for($patient)
        ->create([
            'date' => now()->subDay(),
            'executive_summary' => null,
        ]);

    $response = $this->actingAs($user)->get(route('timeline'));

    $response->assertOk();
    $response->assertSee('Appointment summary generating...', false);
});
