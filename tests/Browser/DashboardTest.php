<?php

declare(strict_types=1);

use App\Models\CommunityEvent;
use App\Models\CommunityPartner;
use App\Models\HealthcareProvider;
use App\Models\Patient;
use App\Models\PatientAppointment;
use App\Models\PatientTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('dashboard displays upcoming appointments and tasks', function () {
    $user = User::factory()->create();
    $patient = Patient::factory()->create(['user_id' => $user->id]);

    $provider = HealthcareProvider::factory()->create();

    // Create upcoming appointment
    $futureAppointment = PatientAppointment::factory()->create([
        'patient_id' => $patient->id,
        'healthcare_provider_id' => $provider->id,
        'date' => now()->addDays(5),
        'time' => '10:00:00',
    ]);

    // Create active task
    $task = PatientTask::factory()->create([
        'patient_id' => $patient->id,
        'description' => 'Test Task',
        'completed_at' => null,
    ]);

    $page = $this->actingAs($user)->visit('/dashboard');

    $page->assertSee('Welcome back, '.$user->name)
        ->assertSee('Upcoming Appointments')
        ->assertSee('Active Tasks')
        ->assertSee($provider->name)
        ->assertSee('Test Task')
        ->assertNoJavascriptErrors();
});

test('dashboard shows empty states when no data exists', function () {
    $user = User::factory()->create();
    Patient::factory()->create(['user_id' => $user->id]);

    $page = $this->actingAs($user)->visit('/dashboard');

    $page->assertSee('No upcoming appointments')
        ->assertSee('No active tasks')
        ->assertNoJavascriptErrors();
});

test('dashboard displays community events', function () {
    $user = User::factory()->create();
    Patient::factory()->create(['user_id' => $user->id]);

    $partner = CommunityPartner::factory()->create(['name' => 'Health Partners']);
    CommunityEvent::factory()->create([
        'community_partner_id' => $partner->id,
        'date' => now()->addDays(10),
        'description' => 'Community Health Fair',
    ]);

    $page = $this->actingAs($user)->visit('/dashboard');

    $page->assertSee('Personalized Feed')
        ->assertSee('Community Event')
        ->assertSee('Health Partners')
        ->assertNoJavascriptErrors();
});

test('dashboard shows executive summary when available', function () {
    $user = User::factory()->create();
    $patient = Patient::factory()->create([
        'user_id' => $user->id,
        'executive_summary' => 'Patient is healthy and doing well.',
        'executive_summary_updated_at' => now(),
    ]);

    $page = $this->actingAs($user)->visit('/dashboard');

    $page->assertSee('Your Health Story in Plain English')
        ->assertSee('Patient is healthy and doing well.')
        ->assertSee('View full timeline')
        ->assertNoJavascriptErrors();
});

test('user can navigate from dashboard to appointments', function () {
    $user = User::factory()->create();
    Patient::factory()->create(['user_id' => $user->id]);

    $page = $this->actingAs($user)->visit('/dashboard');

    $page->click('Appointments')
        ->assertPathIs('/appointments')
        ->assertSee('My Appointments')
        ->assertNoJavascriptErrors();
});

test('user can navigate from dashboard to tasks', function () {
    $user = User::factory()->create();
    Patient::factory()->create(['user_id' => $user->id]);

    $page = $this->actingAs($user)->visit('/dashboard');

    $page->click('Tasks')
        ->assertPathIs('/tasks')
        ->assertSee('My Tasks')
        ->assertNoJavascriptErrors();
});

test('user can navigate from dashboard to timeline', function () {
    $user = User::factory()->create();
    Patient::factory()->create([
        'user_id' => $user->id,
        'executive_summary' => 'Test summary',
    ]);

    $page = $this->actingAs($user)->visit('/dashboard');

    $page->click('View full timeline')
        ->assertPathIs('/timeline')
        ->assertSee('Your Health Story in Plain English')
        ->assertNoJavascriptErrors();
});
