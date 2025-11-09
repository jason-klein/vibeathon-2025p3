<?php

declare(strict_types=1);

use App\Models\HealthcareProvider;
use App\Models\Patient;
use App\Models\PatientAppointment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('timeline displays plain english patient record', function () {
    $user = User::factory()->create();
    $patient = Patient::factory()->create([
        'user_id' => $user->id,
        'plain_english_record' => 'This is the patient health story.',
    ]);

    $page = $this->actingAs($user)->visit('/timeline');

    $page->assertSee('Your Health Story in Plain English')
        ->assertSee('This is the patient health story.')
        ->assertNoJavascriptErrors();
});

test('timeline displays past appointments in chronological order', function () {
    $user = User::factory()->create();
    $patient = Patient::factory()->create(['user_id' => $user->id]);

    $provider = HealthcareProvider::factory()->create(['name' => 'Dr. Johnson']);

    // Create past appointments
    PatientAppointment::factory()->create([
        'patient_id' => $patient->id,
        'healthcare_provider_id' => $provider->id,
        'date' => now()->subDays(30),
        'summary' => 'First visit',
    ]);

    PatientAppointment::factory()->create([
        'patient_id' => $patient->id,
        'healthcare_provider_id' => $provider->id,
        'date' => now()->subDays(10),
        'summary' => 'Follow up visit',
    ]);

    $page = $this->actingAs($user)->visit('/timeline');

    $page->assertSee('Dr. Johnson')
        ->assertSee('First visit')
        ->assertSee('Follow up visit')
        ->assertNoJavascriptErrors();
});

test('timeline does not show future appointments', function () {
    $user = User::factory()->create();
    $patient = Patient::factory()->create(['user_id' => $user->id]);

    $provider = HealthcareProvider::factory()->create();

    // Create future appointment
    PatientAppointment::factory()->create([
        'patient_id' => $patient->id,
        'healthcare_provider_id' => $provider->id,
        'date' => now()->addDays(10),
        'summary' => 'Upcoming appointment',
    ]);

    $page = $this->actingAs($user)->visit('/timeline');

    $page->assertDontSee('Upcoming appointment')
        ->assertNoJavascriptErrors();
});

test('timeline shows empty state when no past appointments exist', function () {
    $user = User::factory()->create();
    Patient::factory()->create(['user_id' => $user->id]);

    $page = $this->actingAs($user)->visit('/timeline');

    $page->assertSee('No past appointments')
        ->assertNoJavascriptErrors();
});

test('timeline displays appointment documents', function () {
    $user = User::factory()->create();
    $patient = Patient::factory()->create(['user_id' => $user->id]);

    $provider = HealthcareProvider::factory()->create();
    $appointment = PatientAppointment::factory()->create([
        'patient_id' => $patient->id,
        'healthcare_provider_id' => $provider->id,
        'date' => now()->subDays(10),
    ]);

    $appointment->documents()->create([
        'file_path' => 'appointment_docs/test.pdf',
        'summary' => 'Lab results',
    ]);

    $page = $this->actingAs($user)->visit('/timeline');

    $page->assertSee('test.pdf')
        ->assertNoJavascriptErrors();
});

test('user can navigate to timeline from dashboard', function () {
    $user = User::factory()->create();
    Patient::factory()->create([
        'user_id' => $user->id,
        'executive_summary' => 'Test summary',
    ]);

    $page = $this->actingAs($user)->visit('/dashboard');

    $page->click('Timeline')
        ->assertPathIs('/timeline')
        ->assertSee('Your Health Story in Plain English')
        ->assertNoJavascriptErrors();
});
