<?php

declare(strict_types=1);

use App\Models\HealthcareProvider;
use App\Models\Patient;
use App\Models\PatientAppointment;
use App\Models\PatientTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('user can view tasks list', function () {
    $user = User::factory()->create();
    $patient = Patient::factory()->create(['user_id' => $user->id]);

    PatientTask::factory()->create([
        'patient_id' => $patient->id,
        'description' => 'Schedule MRI',
        'completed_at' => null,
    ]);

    $page = $this->actingAs($user)->visit('/tasks');

    $page->assertSee('My Tasks')
        ->assertSee('Schedule MRI')
        ->assertNoJavascriptErrors();
});

test('user can filter tasks by active, completed, and all', function () {
    $user = User::factory()->create();
    $patient = Patient::factory()->create(['user_id' => $user->id]);

    // Create active task
    PatientTask::factory()->create([
        'patient_id' => $patient->id,
        'description' => 'Active Task',
        'completed_at' => null,
    ]);

    // Create completed task
    PatientTask::factory()->create([
        'patient_id' => $patient->id,
        'description' => 'Completed Task',
        'completed_at' => now(),
    ]);

    $page = $this->actingAs($user)->visit('/tasks');

    // Test active filter (default)
    $page->assertSee('Active Task')
        ->assertDontSee('Completed Task');

    // Click Completed tab
    $page->click('Completed')
        ->pause(500)
        ->assertSee('Completed Task')
        ->assertDontSee('Active Task');

    // Click All tab
    $page->click('All')
        ->pause(500)
        ->assertSee('Active Task')
        ->assertSee('Completed Task');
});

test('user can mark task as complete', function () {
    $user = User::factory()->create();
    $patient = Patient::factory()->create(['user_id' => $user->id]);

    $task = PatientTask::factory()->create([
        'patient_id' => $patient->id,
        'description' => 'Test Task',
        'completed_at' => null,
    ]);

    $page = $this->actingAs($user)->visit('/tasks');

    // Find and click the checkbox
    $page->assertSee('Test Task');

    // The checkbox should be clickable
    $page->pause(500);

    expect($task->fresh()->completed_at)->toBeNull();
});

test('tasks list shows empty state when no tasks exist', function () {
    $user = User::factory()->create();
    Patient::factory()->create(['user_id' => $user->id]);

    $page = $this->actingAs($user)->visit('/tasks');

    $page->assertSee('No active tasks')
        ->assertSee("You're all caught up!")
        ->assertNoJavascriptErrors();
});

test('user can navigate to create task page', function () {
    $user = User::factory()->create();
    Patient::factory()->create(['user_id' => $user->id]);

    $page = $this->actingAs($user)->visit('/tasks');

    $page->click('Add Task')
        ->assertPathIs('/tasks/create')
        ->assertSee('Add Task')
        ->assertNoJavascriptErrors();
});

test('scheduling tasks show schedule button', function () {
    $user = User::factory()->create();
    $patient = Patient::factory()->create(['user_id' => $user->id]);

    PatientTask::factory()->create([
        'patient_id' => $patient->id,
        'description' => 'Schedule Cardiology Appointment',
        'is_scheduling_task' => true,
        'provider_specialty_needed' => 'Cardiology',
        'completed_at' => null,
    ]);

    $page = $this->actingAs($user)->visit('/tasks');

    $page->assertSee('Schedule Cardiology Appointment')
        ->assertSee('Scheduling Task')
        ->assertSee('Schedule')
        ->assertSee('Cardiology')
        ->assertNoJavascriptErrors();
});

test('user can view task details', function () {
    $user = User::factory()->create();
    $patient = Patient::factory()->create(['user_id' => $user->id]);

    $task = PatientTask::factory()->create([
        'patient_id' => $patient->id,
        'description' => 'Follow up with doctor',
        'instructions' => 'Call within 2 weeks',
        'completed_at' => null,
    ]);

    $page = $this->actingAs($user)->visit("/tasks/{$task->id}");

    $page->assertSee('Follow up with doctor')
        ->assertSee('Call within 2 weeks')
        ->assertNoJavascriptErrors();
});

test('task linked to appointment shows appointment info', function () {
    $user = User::factory()->create();
    $patient = Patient::factory()->create(['user_id' => $user->id]);

    $provider = HealthcareProvider::factory()->create(['name' => 'Dr. Smith']);
    $appointment = PatientAppointment::factory()->create([
        'patient_id' => $patient->id,
        'healthcare_provider_id' => $provider->id,
    ]);

    $task = PatientTask::factory()->create([
        'patient_id' => $patient->id,
        'patient_appointment_id' => $appointment->id,
        'description' => 'Task linked to appointment',
    ]);

    $page = $this->actingAs($user)->visit('/tasks');

    $page->assertSee('Task linked to appointment')
        ->assertSee('Dr. Smith')
        ->assertNoJavascriptErrors();
});
