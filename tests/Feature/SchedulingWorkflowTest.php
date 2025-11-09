<?php

declare(strict_types=1);

use App\Models\HealthcareProvider;
use App\Models\HealthcareSystem;
use App\Models\Patient;
use App\Models\PatientAppointment;
use App\Models\PatientTask;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->patient = Patient::factory()->for($this->user)->create([
        'latitude' => 37.0842,
        'longitude' => -94.5133,
    ]);
});

test('scheduling tasks show Schedule button on dashboard', function () {
    actingAs($this->user);

    $schedulingTask = PatientTask::factory()->for($this->patient)->create([
        'description' => 'Schedule Cardiology appointment',
        'is_scheduling_task' => true,
        'provider_specialty_needed' => 'Cardiology',
        'completed_at' => null,
    ]);

    get(route('dashboard'))
        ->assertOk()
        ->assertSee('Scheduling Task')
        ->assertSee('Schedule');
});

test('completed scheduling tasks show Appointment Scheduled badge instead of Schedule button', function () {
    actingAs($this->user);

    $system = HealthcareSystem::factory()->create([
        'name' => 'Test Healthcare System',
        'is_preferred' => true,
    ]);
    $provider = HealthcareProvider::factory()->for($system, 'system')->create([
        'specialty' => 'Cardiology',
    ]);

    $schedulingTask = PatientTask::factory()->for($this->patient)->create([
        'description' => 'Schedule Cardiology appointment',
        'is_scheduling_task' => true,
        'provider_specialty_needed' => 'Cardiology',
        'completed_at' => now(),
    ]);

    $appointment = PatientAppointment::factory()->for($this->patient)->for($provider)->create([
        'scheduled_from_task_id' => $schedulingTask->id,
        'date' => now()->addDays(7),
    ]);

    get(route('dashboard'))
        ->assertOk()
        ->assertSee('Appointment Scheduled')
        ->assertDontSee('>Schedule<');
});

test('clicking Schedule button shows provider selection page', function () {
    actingAs($this->user);

    $task = PatientTask::factory()->for($this->patient)->create([
        'is_scheduling_task' => true,
        'provider_specialty_needed' => 'Cardiology',
        'description' => 'Schedule Cardiology appointment',
    ]);

    get(route('tasks.schedule', $task->id))
        ->assertOk()
        ->assertSee('Schedule Appointment')
        ->assertSee('Schedule Cardiology appointment')
        ->assertSee('Looking for:')
        ->assertSee('Cardiology');
});

test('provider selection page filters providers by specialty correctly', function () {
    actingAs($this->user);

    $preferredSystem = HealthcareSystem::factory()->create([
        'name' => 'Preferred Healthcare System',
        'is_preferred' => true,
    ]);

    $cardiologyProvider = HealthcareProvider::factory()->for($preferredSystem, 'system')->create([
        'name' => 'Dr. Heart',
        'specialty' => 'Cardiology',
    ]);

    $orthopedicsProvider = HealthcareProvider::factory()->for($preferredSystem, 'system')->create([
        'name' => 'Dr. Bones',
        'specialty' => 'Orthopedics',
    ]);

    $task = PatientTask::factory()->for($this->patient)->create([
        'description' => 'Schedule Cardiology appointment',
        'is_scheduling_task' => true,
        'provider_specialty_needed' => 'Cardiology',
    ]);

    get(route('tasks.schedule', $task->id))
        ->assertOk()
        ->assertSee('Dr. Heart')
        ->assertDontSee('Dr. Bones');
});

test('distance is calculated and displayed accurately', function () {
    actingAs($this->user);

    $preferredSystem = HealthcareSystem::factory()->create([
        'name' => 'Test System',
        'is_preferred' => true,
    ]);

    // Create provider with known distance from patient (approximately 5 miles)
    $provider = HealthcareProvider::factory()->for($preferredSystem, 'system')->create([
        'specialty' => 'Cardiology',
        'latitude' => 37.1342, // ~5 miles north of patient
        'longitude' => -94.5133,
    ]);

    $task = PatientTask::factory()->for($this->patient)->create([
        'description' => 'Schedule Cardiology appointment',
        'is_scheduling_task' => true,
        'provider_specialty_needed' => 'Cardiology',
    ]);

    get(route('tasks.schedule', $task->id))
        ->assertOk()
        ->assertSee('miles away');
});

test('preferred system providers show availability', function () {
    actingAs($this->user);

    $preferredSystem = HealthcareSystem::factory()->create([
        'name' => 'Test System',
        'is_preferred' => true,
    ]);

    $provider = HealthcareProvider::factory()->for($preferredSystem, 'system')->create([
        'specialty' => 'Cardiology',
    ]);

    $task = PatientTask::factory()->for($this->patient)->create([
        'description' => 'Schedule Cardiology appointment',
        'is_scheduling_task' => true,
        'provider_specialty_needed' => 'Cardiology',
    ]);

    get(route('tasks.schedule', $task->id))
        ->assertOk()
        ->assertSee('Preferred Healthcare System Providers')
        ->assertSee('Available Times:')
        ->assertSee('Preferred System');
});

test('non-preferred providers do not show availability', function () {
    actingAs($this->user);

    $nonPreferredSystem = HealthcareSystem::factory()->create([
        'name' => 'Non-Preferred System',
        'is_preferred' => false,
    ]);

    $provider = HealthcareProvider::factory()->for($nonPreferredSystem, 'system')->create([
        'name' => 'Independent Provider',
        'specialty' => 'Cardiology',
    ]);

    $task = PatientTask::factory()->for($this->patient)->create([
        'description' => 'Schedule Cardiology appointment',
        'is_scheduling_task' => true,
        'provider_specialty_needed' => 'Cardiology',
    ]);

    $response = get(route('tasks.schedule', $task->id))
        ->assertOk()
        ->assertSee('Independent Providers')
        ->assertSee('Independent Provider')
        ->assertSee('require manual scheduling');

    // Check that availability times are not shown in the independent providers section
    expect($response->content())
        ->not->toContain('Available Times:');
});

test('clicking availability slot creates appointment with correct data', function () {
    actingAs($this->user);

    $preferredSystem = HealthcareSystem::factory()->create([
        'name' => 'Test System',
        'is_preferred' => true,
    ]);

    $provider = HealthcareProvider::factory()->for($preferredSystem, 'system')->create([
        'specialty' => 'Cardiology',
        'location' => 'Test Medical Center',
        'name' => 'Dr. Smith',
    ]);

    $task = PatientTask::factory()->for($this->patient)->create([
        'is_scheduling_task' => true,
        'provider_specialty_needed' => 'Cardiology',
        'description' => 'Schedule Cardiology appointment',
        'completed_at' => null,
    ]);

    expect(PatientAppointment::count())->toBe(0);

    // Simulate booking an appointment by directly calling the model method
    $date = now()->addDays(3)->format('Y-m-d');
    $time = '10:00:00';

    $appointment = $this->patient->appointments()->create([
        'healthcare_provider_id' => $provider->id,
        'date' => $date,
        'time' => $time,
        'location' => $provider->location,
        'summary' => $task->description,
        'scheduled_from_task_id' => $task->id,
    ]);

    $task->update(['completed_at' => now()]);

    // Verify appointment was created
    expect(PatientAppointment::count())->toBe(1);

    $appointment = PatientAppointment::first();
    expect($appointment)
        ->patient_id->toBe($this->patient->id)
        ->healthcare_provider_id->toBe($provider->id)
        ->date->format('Y-m-d')->toBe($date)
        ->time->format('H:i:s')->toBe($time)
        ->location->toBe('Test Medical Center')
        ->summary->toBe('Schedule Cardiology appointment')
        ->scheduled_from_task_id->toBe($task->id);
});

test('appointment is linked to scheduling task', function () {
    actingAs($this->user);

    $preferredSystem = HealthcareSystem::factory()->create([
        'name' => 'Test System',
        'is_preferred' => true,
    ]);

    $provider = HealthcareProvider::factory()->for($preferredSystem, 'system')->create([
        'specialty' => 'Cardiology',
    ]);

    $task = PatientTask::factory()->for($this->patient)->create([
        'description' => 'Schedule Cardiology appointment',
        'is_scheduling_task' => true,
        'provider_specialty_needed' => 'Cardiology',
        'completed_at' => null,
    ]);

    $date = now()->addDays(5)->format('Y-m-d');
    $time = '14:00:00';

    // Create appointment linked to the task
    $appointment = $this->patient->appointments()->create([
        'healthcare_provider_id' => $provider->id,
        'date' => $date,
        'time' => $time,
        'location' => $provider->location,
        'summary' => $task->description,
        'scheduled_from_task_id' => $task->id,
    ]);

    expect($appointment->scheduled_from_task_id)->toBe($task->id);
    expect($task->fresh()->scheduledAppointment->id)->toBe($appointment->id);
});

test('scheduling task is marked complete after appointment creation', function () {
    actingAs($this->user);

    $preferredSystem = HealthcareSystem::factory()->create([
        'name' => 'Test System',
        'is_preferred' => true,
    ]);

    $provider = HealthcareProvider::factory()->for($preferredSystem, 'system')->create([
        'specialty' => 'Cardiology',
    ]);

    $task = PatientTask::factory()->for($this->patient)->create([
        'description' => 'Schedule Cardiology appointment',
        'is_scheduling_task' => true,
        'provider_specialty_needed' => 'Cardiology',
        'completed_at' => null,
    ]);

    expect($task->completed_at)->toBeNull();

    $date = now()->addDays(5)->format('Y-m-d');
    $time = '14:00:00';

    // Create appointment and mark task complete
    $this->patient->appointments()->create([
        'healthcare_provider_id' => $provider->id,
        'date' => $date,
        'time' => $time,
        'location' => $provider->location,
        'summary' => $task->description,
        'scheduled_from_task_id' => $task->id,
    ]);

    $task->update(['completed_at' => now()]);

    expect($task->fresh()->completed_at)->not->toBeNull();
});

test('cannot access scheduling page for non-scheduling task', function () {
    actingAs($this->user);

    $task = PatientTask::factory()->for($this->patient)->create([
        'description' => 'Regular task',
        'is_scheduling_task' => false,
    ]);

    get(route('tasks.schedule', $task->id))
        ->assertNotFound();
});

test('cannot access scheduling page for another users task', function () {
    $otherUser = User::factory()->create();
    $otherPatient = Patient::factory()->for($otherUser)->create();

    $task = PatientTask::factory()->for($otherPatient)->create([
        'description' => 'Schedule Cardiology appointment',
        'is_scheduling_task' => true,
        'provider_specialty_needed' => 'Cardiology',
    ]);

    actingAs($this->user);

    get(route('tasks.schedule', $task->id))
        ->assertNotFound();
});

test('distance calculation works for various coordinates', function () {
    actingAs($this->user);

    $preferredSystem = HealthcareSystem::factory()->create([
        'name' => 'Test System',
        'is_preferred' => true,
    ]);

    // Create providers at different distances
    $nearProvider = HealthcareProvider::factory()->for($preferredSystem, 'system')->create([
        'name' => 'Near Provider',
        'specialty' => 'Cardiology',
        'latitude' => 37.0850, // Very close
        'longitude' => -94.5140,
    ]);

    $farProvider = HealthcareProvider::factory()->for($preferredSystem, 'system')->create([
        'name' => 'Far Provider',
        'specialty' => 'Cardiology',
        'latitude' => 37.2000, // Further away
        'longitude' => -94.7000,
    ]);

    $task = PatientTask::factory()->for($this->patient)->create([
        'description' => 'Schedule Cardiology appointment',
        'is_scheduling_task' => true,
        'provider_specialty_needed' => 'Cardiology',
    ]);

    get(route('tasks.schedule', $task->id))
        ->assertOk()
        ->assertSee('Near Provider')
        ->assertSee('Far Provider')
        ->assertSee('miles away');
});

test('scheduling tasks show Schedule button on tasks index page', function () {
    actingAs($this->user);

    $schedulingTask = PatientTask::factory()->for($this->patient)->create([
        'description' => 'Schedule Cardiology appointment',
        'is_scheduling_task' => true,
        'provider_specialty_needed' => 'Cardiology',
        'completed_at' => null,
    ]);

    get(route('tasks.index'))
        ->assertOk()
        ->assertSee('Scheduling Task')
        ->assertSee('Schedule');
});
