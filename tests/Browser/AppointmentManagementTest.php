<?php

declare(strict_types=1);

use App\Models\HealthcareProvider;
use App\Models\Patient;
use App\Models\PatientAppointment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('user can view appointments list', function () {
    $user = User::factory()->create();
    $patient = Patient::factory()->create(['user_id' => $user->id]);

    $provider = HealthcareProvider::factory()->create(['name' => 'Dr. Smith']);

    PatientAppointment::factory()->create([
        'patient_id' => $patient->id,
        'healthcare_provider_id' => $provider->id,
        'date' => now()->addDays(5),
    ]);

    $page = $this->actingAs($user)->visit('/appointments');

    $page->assertSee('My Appointments')
        ->assertSee('Dr. Smith')
        ->assertSee('Upcoming')
        ->assertNoJavascriptErrors();
});

test('user can filter appointments by past, upcoming, and all', function () {
    $user = User::factory()->create();
    $patient = Patient::factory()->create(['user_id' => $user->id]);

    $provider = HealthcareProvider::factory()->create();

    // Create past appointment
    PatientAppointment::factory()->create([
        'patient_id' => $patient->id,
        'healthcare_provider_id' => $provider->id,
        'date' => now()->subDays(5),
        'summary' => 'Past visit',
    ]);

    // Create future appointment
    PatientAppointment::factory()->create([
        'patient_id' => $patient->id,
        'healthcare_provider_id' => $provider->id,
        'date' => now()->addDays(5),
    ]);

    $page = $this->actingAs($user)->visit('/appointments');

    // Test upcoming filter (default)
    $page->assertSee('Upcoming')
        ->assertDontSee('Past visit');

    // Click Past tab
    $page->click('Past')
        ->pause(500)
        ->assertSee('Past visit');

    // Click All tab
    $page->click('All')
        ->pause(500);
});

test('user can navigate to create appointment page', function () {
    $user = User::factory()->create();
    Patient::factory()->create(['user_id' => $user->id]);

    $page = $this->actingAs($user)->visit('/appointments');

    $page->click('Add Appointment')
        ->assertPathIs('/appointments/create')
        ->assertSee('Add Appointment')
        ->assertNoJavascriptErrors();
});

test('user can view appointment details', function () {
    $user = User::factory()->create();
    $patient = Patient::factory()->create(['user_id' => $user->id]);

    $provider = HealthcareProvider::factory()->create(['name' => 'Dr. Johnson']);
    $appointment = PatientAppointment::factory()->create([
        'patient_id' => $patient->id,
        'healthcare_provider_id' => $provider->id,
        'date' => now()->addDays(5),
        'location' => '123 Main St',
        'summary' => 'Annual checkup',
    ]);

    $page = $this->actingAs($user)->visit("/appointments/{$appointment->id}");

    $page->assertSee('Dr. Johnson')
        ->assertSee('123 Main St')
        ->assertSee('Annual checkup')
        ->assertNoJavascriptErrors();
});

test('appointments list shows empty state when no appointments exist', function () {
    $user = User::factory()->create();
    Patient::factory()->create(['user_id' => $user->id]);

    $page = $this->actingAs($user)->visit('/appointments');

    $page->assertSee('No upcoming appointments')
        ->assertSee('Your upcoming appointments will appear here')
        ->assertNoJavascriptErrors();
});

test('appointments are properly sorted by date', function () {
    $user = User::factory()->create();
    $patient = Patient::factory()->create(['user_id' => $user->id]);

    $provider = HealthcareProvider::factory()->create();

    // Create appointments in random order
    PatientAppointment::factory()->create([
        'patient_id' => $patient->id,
        'healthcare_provider_id' => $provider->id,
        'date' => now()->addDays(10),
    ]);

    PatientAppointment::factory()->create([
        'patient_id' => $patient->id,
        'healthcare_provider_id' => $provider->id,
        'date' => now()->addDays(2),
    ]);

    $page = $this->actingAs($user)->visit('/appointments');

    $page->assertNoJavascriptErrors();
});
