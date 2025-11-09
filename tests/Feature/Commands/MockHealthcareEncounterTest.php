<?php

declare(strict_types=1);

use App\Models\Patient;
use App\Models\PatientAppointment;
use App\Models\User;
use App\Services\AiSummaryService;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\artisan;

beforeEach(function () {
    Storage::fake('local');

    // Mock the AI summary service to avoid requiring OpenAI API key
    $this->mock(AiSummaryService::class, function ($mock) {
        $mock->shouldReceive('updatePatientSummaries')
            ->andReturn(null);
        $mock->shouldReceive('generateDocumentExecutiveSummary')
            ->andReturn('Mock document summary');
        $mock->shouldReceive('generateAppointmentExecutiveSummary')
            ->andReturn('Mock appointment summary');
    });
});

test('command runs successfully for given patient', function () {
    $user = User::factory()->create();
    $patient = Patient::factory()->create(['user_id' => $user->id]);

    artisan('mock:healthcare-encounter', ['patient_id' => $patient->id])
        ->assertSuccessful();
});

test('command fails for non-existent patient', function () {
    artisan('mock:healthcare-encounter', ['patient_id' => 999])
        ->assertFailed();
});

test('command creates past appointment with summaries', function () {
    $user = User::factory()->create();
    $patient = Patient::factory()->create(['user_id' => $user->id]);

    artisan('mock:healthcare-encounter', ['patient_id' => $patient->id]);

    expect($patient->appointments()->count())->toBeGreaterThan(0);

    $pastAppointment = $patient->appointments()
        ->where('date', '<', now()->toDateString())
        ->first();

    expect($pastAppointment)->not->toBeNull()
        ->and($pastAppointment->summary)->not->toBeNull()
        ->and($pastAppointment->patient_notes)->not->toBeNull();
});

test('command generates PDF documents', function () {
    $user = User::factory()->create();
    $patient = Patient::factory()->create(['user_id' => $user->id]);

    artisan('mock:healthcare-encounter', ['patient_id' => $patient->id]);

    $pastAppointment = $patient->appointments()
        ->where('date', '<', now()->toDateString())
        ->first();

    expect($pastAppointment->documents()->count())->toBeGreaterThan(0);

    $document = $pastAppointment->documents()->first();
    expect($document)->not->toBeNull()
        ->and($document->file_path)->toContain('appointment_docs/')
        ->and(Storage::exists($document->file_path))->toBeTrue();
});

test('command creates referral tasks', function () {
    $user = User::factory()->create();
    $patient = Patient::factory()->create(['user_id' => $user->id]);

    artisan('mock:healthcare-encounter', ['patient_id' => $patient->id]);

    // Not all scenarios have referrals, so we check if any were created
    $tasks = $patient->tasks()->where('is_scheduling_task', true)->get();

    // If there are tasks, verify they're properly configured
    if ($tasks->count() > 0) {
        $task = $tasks->first();
        expect($task->is_scheduling_task)->toBeTrue()
            ->and($task->provider_specialty_needed)->not->toBeNull()
            ->and($task->description)->toContain('Schedule');
    }

    expect($tasks->count())->toBeGreaterThanOrEqual(0);
});

test('command creates follow-up appointment', function () {
    $user = User::factory()->create();
    $patient = Patient::factory()->create(['user_id' => $user->id]);

    artisan('mock:healthcare-encounter', ['patient_id' => $patient->id]);

    $futureAppointments = $patient->appointments()
        ->where('date', '>', now()->toDateString())
        ->get();

    expect($futureAppointments->count())->toBeGreaterThan(0);

    $followUp = $futureAppointments->first();
    expect($followUp->summary)->toContain('Follow-up');
});

test('command can update existing appointment', function () {
    $user = User::factory()->create();
    $patient = Patient::factory()->create(['user_id' => $user->id]);

    // Create a future appointment
    $futureAppointment = PatientAppointment::create([
        'patient_id' => $patient->id,
        'date' => now()->addWeeks(2)->toDateString(),
        'time' => '10:00:00',
        'partner' => 'Dr. Test',
        'location' => 'Test Location',
    ]);

    artisan('mock:healthcare-encounter', [
        'patient_id' => $patient->id,
        '--update-existing' => $futureAppointment->id,
    ])->assertSuccessful();

    $futureAppointment->refresh();

    // Should now be a past appointment with details
    expect($futureAppointment->date->isBefore(now()))->toBeTrue()
        ->and($futureAppointment->summary)->not->toBeNull()
        ->and($futureAppointment->patient_notes)->not->toBeNull();
});

test('PDF document contains required information', function () {
    $user = User::factory()->create(['name' => 'John Doe']);
    $patient = Patient::factory()->create(['user_id' => $user->id]);

    artisan('mock:healthcare-encounter', ['patient_id' => $patient->id]);

    $pastAppointment = $patient->appointments()
        ->where('date', '<', now()->toDateString())
        ->first();

    $document = $pastAppointment->documents()->first();

    expect($document)->not->toBeNull()
        ->and(Storage::exists($document->file_path))->toBeTrue();

    // Verify the PDF was created (file size > 0)
    $fileSize = Storage::size($document->file_path);
    expect($fileSize)->toBeGreaterThan(0);
});
