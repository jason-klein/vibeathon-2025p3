<?php

use App\Models\HealthcareProvider;
use App\Models\HealthcareSystem;
use App\Models\PatientAppointment;
use App\Models\PatientAppointmentDocument;
use App\Models\PatientTask;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $this->get('/timeline')->assertRedirect('/login');
});

test('authenticated users can visit the timeline page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/timeline')->assertSuccessful();
});

test('timeline page displays plain english patient record when available', function () {
    $user = User::factory()->create();
    $patient = $user->patient;
    $patient->update([
        'plain_english_record' => 'This is a comprehensive health history for the patient.',
        'executive_summary_updated_at' => now()->subDays(3),
    ]);

    $response = $this->actingAs($user)->get('/timeline');

    $response->assertSuccessful();
    $response->assertSee('Your Health Story in Plain English');
    $response->assertSee('This is a comprehensive health history');
    $response->assertSee('Last updated');
});

test('timeline page displays executive summary when available', function () {
    $user = User::factory()->create();
    $patient = $user->patient;
    $patient->update([
        'executive_summary' => 'Patient is managing their conditions well.',
        'executive_summary_updated_at' => now()->subDays(2),
    ]);

    $response = $this->actingAs($user)->get('/timeline');

    $response->assertSuccessful();
    $response->assertSee('Current Health Summary');
    $response->assertSee('Patient is managing their conditions well');
});

test('timeline displays past appointments in reverse chronological order', function () {
    $user = User::factory()->create();
    $patient = $user->patient;

    $system = HealthcareSystem::factory()->create(['name' => 'Test System']);
    $provider = HealthcareProvider::factory()->for($system, 'system')->create([
        'name' => 'Dr. Smith',
        'specialty' => 'Family Medicine',
    ]);

    // Create appointments in different order
    $appointment1 = PatientAppointment::factory()->for($patient)->create([
        'healthcare_provider_id' => $provider->id,
        'date' => now()->subDays(30),
        'summary' => 'First visit summary',
    ]);

    $appointment2 = PatientAppointment::factory()->for($patient)->create([
        'healthcare_provider_id' => $provider->id,
        'date' => now()->subDays(15),
        'summary' => 'Second visit summary',
    ]);

    $appointment3 = PatientAppointment::factory()->for($patient)->create([
        'healthcare_provider_id' => $provider->id,
        'date' => now()->subDays(5),
        'summary' => 'Most recent visit summary',
    ]);

    $response = $this->actingAs($user)->get('/timeline');

    $response->assertSuccessful();
    $response->assertSee('Dr. Smith');
    $response->assertSee('Family Medicine');
    $response->assertSee('First visit summary');
    $response->assertSee('Second visit summary');
    $response->assertSee('Most recent visit summary');

    // Verify reverse chronological order (most recent first)
    $content = $response->getContent();
    $pos1 = strpos($content, 'Most recent visit summary');
    $pos2 = strpos($content, 'Second visit summary');
    $pos3 = strpos($content, 'First visit summary');

    expect($pos1)->toBeLessThan($pos2);
    expect($pos2)->toBeLessThan($pos3);
});

test('timeline does not show future appointments', function () {
    $user = User::factory()->create();
    $patient = $user->patient;

    $system = HealthcareSystem::factory()->create(['name' => 'Test System']);
    $provider = HealthcareProvider::factory()->for($system, 'system')->create([
        'name' => 'Dr. Future',
    ]);

    // Create past appointment
    PatientAppointment::factory()->for($patient)->create([
        'healthcare_provider_id' => $provider->id,
        'date' => now()->subDays(5),
        'summary' => 'Past appointment',
    ]);

    // Create future appointment
    PatientAppointment::factory()->for($patient)->create([
        'healthcare_provider_id' => $provider->id,
        'date' => now()->addDays(10),
        'summary' => 'Future appointment',
    ]);

    $response = $this->actingAs($user)->get('/timeline');

    $response->assertSuccessful();
    $response->assertSee('Past appointment');
    $response->assertDontSee('Future appointment');
});

test('timeline displays visit summaries and patient notes', function () {
    $user = User::factory()->create();
    $patient = $user->patient;

    $system = HealthcareSystem::factory()->create(['name' => 'Test System']);
    $provider = HealthcareProvider::factory()->for($system, 'system')->create([
        'name' => 'Dr. Test',
    ]);

    PatientAppointment::factory()->for($patient)->create([
        'healthcare_provider_id' => $provider->id,
        'date' => now()->subDays(10),
        'summary' => 'Visit summary with details',
        'patient_notes' => 'My personal notes about this visit',
    ]);

    $response = $this->actingAs($user)->get('/timeline');

    $response->assertSuccessful();
    $response->assertSee('Visit Summary');
    $response->assertSee('Visit summary with details');
    $response->assertSee('My Notes');
    $response->assertSee('My personal notes about this visit');
});

test('timeline displays attached documents with download links', function () {
    $user = User::factory()->create();
    $patient = $user->patient;

    $system = HealthcareSystem::factory()->create(['name' => 'Test System']);
    $provider = HealthcareProvider::factory()->for($system, 'system')->create([
        'name' => 'Dr. Test',
    ]);

    $appointment = PatientAppointment::factory()->for($patient)->create([
        'healthcare_provider_id' => $provider->id,
        'date' => now()->subDays(10),
    ]);

    PatientAppointmentDocument::factory()->for($appointment, 'appointment')->create([
        'file_path' => 'appointment_docs/test-document.pdf',
        'summary' => 'Lab results document',
    ]);

    $response = $this->actingAs($user)->get('/timeline');

    $response->assertSuccessful();
    $response->assertSee('Documents (1)');
    $response->assertSee('test-document.pdf');
    $response->assertSee('Lab results document');
});

test('timeline displays related tasks for each encounter', function () {
    $user = User::factory()->create();
    $patient = $user->patient;

    $system = HealthcareSystem::factory()->create(['name' => 'Test System']);
    $provider = HealthcareProvider::factory()->for($system, 'system')->create([
        'name' => 'Dr. Test',
    ]);

    $appointment = PatientAppointment::factory()->for($patient)->create([
        'healthcare_provider_id' => $provider->id,
        'date' => now()->subDays(10),
    ]);

    // Create completed task
    PatientTask::factory()->for($patient)->create([
        'patient_appointment_id' => $appointment->id,
        'description' => 'Follow-up blood work',
        'completed_at' => now()->subDays(5),
    ]);

    // Create incomplete task
    PatientTask::factory()->for($patient)->create([
        'patient_appointment_id' => $appointment->id,
        'description' => 'Schedule MRI appointment',
        'completed_at' => null,
    ]);

    $response = $this->actingAs($user)->get('/timeline');

    $response->assertSuccessful();
    $response->assertSee('Related Tasks (2)');
    $response->assertSee('Follow-up blood work');
    $response->assertSee('Schedule MRI appointment');
    $response->assertSee('Completed');
});

test('timeline shows empty state when no past appointments exist', function () {
    $user = User::factory()->create();
    $patient = $user->patient;

    // Create a future appointment (should not be displayed)
    $system = HealthcareSystem::factory()->create(['name' => 'Test System']);
    $provider = HealthcareProvider::factory()->for($system, 'system')->create();

    PatientAppointment::factory()->for($patient)->create([
        'healthcare_provider_id' => $provider->id,
        'date' => now()->addDays(5),
    ]);

    $response = $this->actingAs($user)->get('/timeline');

    $response->assertSuccessful();
    $response->assertSee('No past appointments yet');
    $response->assertSee('Your healthcare encounters will appear here');
});

test('timeline has back to dashboard link', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/timeline');

    $response->assertSuccessful();
    $response->assertSee('Back to Dashboard');
});

test('timeline page title is correct', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/timeline');

    $response->assertSuccessful();
    $response->assertSee('Your Health Timeline');
});

test('timeline displays link to full appointment details', function () {
    $user = User::factory()->create();
    $patient = $user->patient;

    $system = HealthcareSystem::factory()->create(['name' => 'Test System']);
    $provider = HealthcareProvider::factory()->for($system, 'system')->create([
        'name' => 'Dr. Test',
    ]);

    $appointment = PatientAppointment::factory()->for($patient)->create([
        'healthcare_provider_id' => $provider->id,
        'date' => now()->subDays(10),
    ]);

    $response = $this->actingAs($user)->get('/timeline');

    $response->assertSuccessful();
    $response->assertSee('View full appointment details');
});
