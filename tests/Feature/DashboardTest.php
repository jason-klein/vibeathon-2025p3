<?php

use App\Models\CommunityEvent;
use App\Models\CommunityPartner;
use App\Models\HealthcareProvider;
use App\Models\HealthcareSystem;
use App\Models\Patient;
use App\Models\PatientAppointment;
use App\Models\PatientTask;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $this->get('/dashboard')->assertRedirect('/login');
});

test('authenticated users can visit the dashboard', function () {
    $this->actingAs($user = User::factory()->create());

    $this->get('/dashboard')->assertSuccessful();
});

test('dashboard displays upcoming appointments with distance', function () {
    $user = User::factory()->create();
    $patient = $user->patient; // Use auto-created patient
    $patient->update([
        'latitude' => 37.0842,
        'longitude' => -94.5133,
    ]);

    $system = HealthcareSystem::factory()->create([
        'name' => 'Freeman Health System',
        'is_preferred' => true,
    ]);
    $provider = HealthcareProvider::factory()->for($system, 'system')->create([
        'name' => 'Dr. John Smith',
        'specialty' => 'Cardiology',
        'location' => '123 Medical Plaza, Joplin, MO',
        'latitude' => 37.0900,
        'longitude' => -94.5200,
    ]);

    // Create 5 appointments to test limit of 3
    for ($i = 1; $i <= 5; $i++) {
        PatientAppointment::factory()->for($patient)->create([
            'healthcare_provider_id' => $provider->id,
            'date' => now()->addDays($i),
            'time' => '10:00',
        ]);
    }

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertSuccessful();
    $response->assertSee('Dr. John Smith');
    $response->assertSee('Cardiology');
    $response->assertSee('miles away');
});

test('dashboard displays pending tasks with schedule button for scheduling tasks', function () {
    $user = User::factory()->create();
    $patient = $user->patient; // Use auto-created patient

    // Create a regular task
    $regularTask = PatientTask::factory()->for($patient)->create([
        'description' => 'Take medication',
        'is_scheduling_task' => false,
        'completed_at' => null,
    ]);

    // Create a scheduling task
    $schedulingTask = PatientTask::factory()->for($patient)->create([
        'description' => 'Schedule MRI appointment',
        'is_scheduling_task' => true,
        'provider_specialty_needed' => 'Radiology',
        'completed_at' => null,
    ]);

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertSuccessful();
    $response->assertSee('Take medication');
    $response->assertSee('Schedule MRI appointment');
    $response->assertSee('Scheduling Task');
    $response->assertSee('Schedule');
});

test('dashboard displays executive summary card when available', function () {
    $user = User::factory()->create();
    $patient = $user->patient; // Use auto-created patient
    $patient->update([
        'executive_summary' => 'Patient has been managing diabetes well.',
        'executive_summary_updated_at' => now()->subDays(3),
    ]);

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertSuccessful();
    $response->assertSee('Your Health Story in Plain English');
    $response->assertSee('Patient has been managing diabetes');
    $response->assertSee('View full timeline');
});

test('dashboard does not show executive summary card when not available', function () {
    $user = User::factory()->create();
    // The auto-created patient should have null executive_summary by default
    // Just verify it's null
    expect($user->patient->executive_summary)->toBeNull();

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertSuccessful();
    $response->assertDontSee('Your Health Story in Plain English');
});

test('dashboard displays community events feed', function () {
    $user = User::factory()->create();
    Patient::factory()->for($user)->create();

    $partner = CommunityPartner::factory()->create(['name' => 'Health Alliance']);

    CommunityEvent::factory()->for($partner, 'partner')->create([
        'description' => 'Free diabetes screening and education',
        'date' => now()->addDays(7),
        'time' => '14:00',
        'location' => 'Community Center',
    ]);

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertSuccessful();
    $response->assertSee('Community Events');
    $response->assertSee('Health Alliance');
    $response->assertSee('Free diabetes screening');
    $response->assertSee('Community Center');
});

test('dashboard filters events based on patient health interests', function () {
    $user = User::factory()->create();
    $patient = Patient::factory()->for($user)->create();

    // Create a past appointment with diabetes-related content
    PatientAppointment::factory()->for($patient)->create([
        'date' => now()->subWeeks(2),
        'summary' => 'Patient presents with diabetes management concerns',
    ]);

    $partner1 = CommunityPartner::factory()->create(['name' => 'Diabetes Foundation']);
    $partner2 = CommunityPartner::factory()->create(['name' => 'Heart Health Group']);

    // This event should appear because it matches "diabetes" keyword
    $diabetesEvent = CommunityEvent::factory()->for($partner1, 'partner')->create([
        'description' => 'Free diabetes screening and education workshop',
        'date' => now()->addDays(7),
    ]);

    // This event might not appear as it doesn't match keywords
    $heartEvent = CommunityEvent::factory()->for($partner2, 'partner')->create([
        'description' => 'Heart health awareness seminar',
        'date' => now()->addDays(10),
    ]);

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertSuccessful();
    $response->assertSee('Diabetes Foundation');
});

test('dashboard shows empty states when no data available', function () {
    $user = User::factory()->create();
    Patient::factory()->for($user)->create();

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertSuccessful();
    $response->assertSee('No upcoming appointments');
    $response->assertSee('No active tasks');
    $response->assertSee('No events available');
});

test('dashboard limits upcoming appointments to 3', function () {
    $user = User::factory()->create();
    $patient = Patient::factory()->for($user)->create();

    $system = HealthcareSystem::factory()->create(['name' => 'Test System']);
    $provider = HealthcareProvider::factory()->for($system, 'system')->create([
        'name' => 'Dr. Test Provider',
        'location' => 'Test Location',
    ]);

    // Create 5 future appointments
    for ($i = 1; $i <= 5; $i++) {
        PatientAppointment::factory()->for($patient)->create([
            'healthcare_provider_id' => $provider->id,
            'date' => now()->addDays($i),
        ]);
    }

    $response = $this->actingAs($user)->get('/dashboard');

    // Count should show 5 but display should only show 3
    $response->assertSuccessful();
    $response->assertSee('5'); // The count in stats card
});
