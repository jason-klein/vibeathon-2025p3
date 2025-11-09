<?php

declare(strict_types=1);

use App\Models\HealthcareProvider;
use App\Models\HealthcareSystem;
use App\Models\PatientTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

test('booking appointment dispatches toast with correct format', function () {
    $user = User::factory()->create();
    $patient = $user->patient;

    $system = HealthcareSystem::factory()->create(['is_preferred' => true]);
    $provider = HealthcareProvider::factory()->create([
        'healthcare_system_id' => $system->id,
        'name' => 'Dr. Smith',
    ]);

    $task = PatientTask::factory()->for($patient)->create([
        'is_scheduling_task' => true,
        'description' => 'Schedule cardiology appointment',
        'completed_at' => null,
    ]);

    $this->actingAs($user);
    Volt::test('tasks.schedule', ['taskId' => $task->id])
        ->call('bookAppointment', $provider->id, '2025-12-01', '10:00:00')
        ->assertDispatched('toast')
        ->assertHasNoErrors();

    // Verify appointment was created
    expect($patient->appointments()->count())->toBe(1);

    $appointment = $patient->appointments()->first();
    expect($appointment->healthcare_provider_id)->toBe($provider->id);
    expect($appointment->date->format('Y-m-d'))->toBe('2025-12-01');
    expect($appointment->time->format('H:i:s'))->toBe('10:00:00');

    // Verify task was marked as completed
    $task->refresh();
    expect($task->completed_at)->not->toBeNull();
});

test('user can access schedule page for valid scheduling task', function () {
    $user = User::factory()->create();
    $patient = $user->patient;

    $task = PatientTask::factory()->for($patient)->create([
        'is_scheduling_task' => true,
        'description' => 'Schedule appointment',
    ]);

    $response = $this->actingAs($user)->get("/tasks/{$task->id}/schedule");

    $response->assertOk()
        ->assertSee('Schedule Appointment')
        ->assertSee('Schedule appointment');
});

test('user cannot access schedule page for non-scheduling task', function () {
    $user = User::factory()->create();
    $patient = $user->patient;

    $task = PatientTask::factory()->for($patient)->create([
        'is_scheduling_task' => false,
        'description' => 'Regular task',
    ]);

    $response = $this->actingAs($user)->get("/tasks/{$task->id}/schedule");

    $response->assertNotFound();
});
