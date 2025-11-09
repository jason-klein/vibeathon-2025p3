<?php

declare(strict_types=1);

use App\Models\HealthcareProvider;
use App\Models\HealthcareSystem;
use App\Models\PatientAppointment;
use App\Models\PatientTask;
use App\Models\User;
use Livewire\Volt\Volt;

test('guests cannot access tasks index', function () {
    $this->get('/tasks')->assertRedirect('/login');
});

test('authenticated users can view tasks index', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/tasks')
        ->assertSuccessful()
        ->assertSee('My Tasks');
});

test('user can view all their tasks', function () {
    $user = User::factory()->create();
    $patient = $user->patient;

    // Create active and completed tasks
    PatientTask::factory()->for($patient)->create([
        'description' => 'Active task',
        'completed_at' => null,
    ]);

    PatientTask::factory()->for($patient)->create([
        'description' => 'Completed task',
        'completed_at' => now()->subDays(2),
    ]);

    $response = $this->actingAs($user)->get('/tasks');

    $response->assertSuccessful();
    // Active filter is default, so only active task should show
    $response->assertSee('Active task');
});

test('tasks are filtered correctly by active, completed, and all', function () {
    $user = User::factory()->create();
    $patient = $user->patient;

    // Create active task
    $activeTask = PatientTask::factory()->for($patient)->create([
        'description' => 'This is an active task',
        'completed_at' => null,
    ]);

    // Create completed task
    $completedTask = PatientTask::factory()->for($patient)->create([
        'description' => 'This is a completed task',
        'completed_at' => now()->subDays(2),
    ]);

    // Test active filter (default)
    $this->actingAs($user);
    Volt::test('tasks.index')
        ->assertSee('This is an active task')
        ->assertDontSee('This is a completed task');

    // Test completed filter
    $this->actingAs($user);
    Volt::test('tasks.index')
        ->set('filter', 'completed')
        ->assertSee('This is a completed task')
        ->assertDontSee('This is an active task');

    // Test all filter
    $this->actingAs($user);
    Volt::test('tasks.index')
        ->set('filter', 'all')
        ->assertSee('This is an active task')
        ->assertSee('This is a completed task');
});

test('user can only see their own tasks', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $patient1 = $user1->patient;
    $patient2 = $user2->patient;

    PatientTask::factory()->for($patient1)->create([
        'description' => 'User 1 task',
    ]);

    PatientTask::factory()->for($patient2)->create([
        'description' => 'User 2 task',
    ]);

    $this->actingAs($user1);
    Volt::test('tasks.index')
        ->assertSee('User 1 task')
        ->assertDontSee('User 2 task');
});

test('user can toggle task completion from index page', function () {
    $user = User::factory()->create();
    $patient = $user->patient;

    $task = PatientTask::factory()->for($patient)->create([
        'description' => 'Test task',
        'completed_at' => null,
        'is_scheduling_task' => false,
    ]);

    $this->actingAs($user);
    Volt::test('tasks.index')
        ->call('toggleComplete', $task->id);

    expect($task->fresh()->completed_at)->not->toBeNull();

    // Toggle back to incomplete
    $this->actingAs($user);
    Volt::test('tasks.index')
        ->call('toggleComplete', $task->id);

    expect($task->fresh()->completed_at)->toBeNull();
});

test('tasks show appointment link when linked to appointment', function () {
    $user = User::factory()->create();
    $patient = $user->patient;

    $system = HealthcareSystem::factory()->create(['name' => 'Test Healthcare System']);
    $provider = HealthcareProvider::factory()->for($system, 'system')->create(['name' => 'Dr. Test Provider']);

    $appointment = PatientAppointment::factory()->for($patient)->create([
        'healthcare_provider_id' => $provider->id,
    ]);

    $task = PatientTask::factory()->for($patient)->create([
        'description' => 'Follow up test',
        'patient_appointment_id' => $appointment->id,
    ]);

    $this->actingAs($user);
    Volt::test('tasks.index')
        ->assertSee('Follow up test')
        ->assertSee('Dr. Test Provider');
});

test('scheduling tasks display correctly', function () {
    $user = User::factory()->create();
    $patient = $user->patient;

    $task = PatientTask::factory()->for($patient)->create([
        'description' => 'Schedule MRI',
        'is_scheduling_task' => true,
        'provider_specialty_needed' => 'Radiology',
    ]);

    $this->actingAs($user);
    Volt::test('tasks.index')
        ->assertSee('Schedule MRI')
        ->assertSee('Scheduling Task')
        ->assertSee('Radiology')
        ->assertSee('Schedule');
});

test('user can create a new task', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/tasks/create')->assertSuccessful();

    $this->actingAs($user);
    Volt::test('tasks.create')
        ->set('description', 'New test task')
        ->set('instructions', 'Some instructions')
        ->call('save')
        ->assertRedirect(route('tasks.index'));

    expect(PatientTask::where('description', 'New test task')->exists())->toBeTrue();
});

test('user can create task with appointment link', function () {
    $user = User::factory()->create();
    $patient = $user->patient;

    $appointment = PatientAppointment::factory()->for($patient)->create();

    $this->actingAs($user);
    Volt::test('tasks.create')
        ->set('description', 'Task linked to appointment')
        ->set('patient_appointment_id', $appointment->id)
        ->call('save')
        ->assertRedirect(route('tasks.index'));

    $task = PatientTask::where('description', 'Task linked to appointment')->first();
    expect($task)->not->toBeNull();
    expect($task->patient_appointment_id)->toBe($appointment->id);
});

test('user can create scheduling task', function () {
    $user = User::factory()->create();

    $this->actingAs($user);
    Volt::test('tasks.create')
        ->set('description', 'Schedule cardiology appointment')
        ->set('is_scheduling_task', true)
        ->set('provider_specialty_needed', 'Cardiology')
        ->call('save')
        ->assertRedirect(route('tasks.index'));

    $task = PatientTask::where('description', 'Schedule cardiology appointment')->first();
    expect($task)->not->toBeNull();
    expect($task->is_scheduling_task)->toBeTrue();
    expect($task->provider_specialty_needed)->toBe('Cardiology');
});

test('task description is required', function () {
    $user = User::factory()->create();

    $this->actingAs($user);
    Volt::test('tasks.create')
        ->set('description', '')
        ->call('save')
        ->assertHasErrors(['description']);
});

test('user can view task details', function () {
    $user = User::factory()->create();
    $patient = $user->patient;

    $task = PatientTask::factory()->for($patient)->create([
        'description' => 'Test task details',
        'instructions' => 'Test instructions',
    ]);

    $this->actingAs($user)
        ->get(route('tasks.show', $task))
        ->assertSuccessful()
        ->assertSee('Test task details')
        ->assertSee('Test instructions');
});

test('user cannot view another users task', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $patient2 = $user2->patient;

    $task = PatientTask::factory()->for($patient2)->create([
        'description' => 'User 2 task',
    ]);

    $this->actingAs($user1)
        ->get(route('tasks.show', $task))
        ->assertForbidden();
});

test('user can toggle task completion from details page', function () {
    $user = User::factory()->create();
    $patient = $user->patient;

    $task = PatientTask::factory()->for($patient)->create([
        'description' => 'Test task',
        'completed_at' => null,
    ]);

    $this->actingAs($user);
    Volt::test('tasks.show', ['taskId' => $task->id])
        ->call('toggleComplete');

    expect($task->fresh()->completed_at)->not->toBeNull();
});

test('user can edit task', function () {
    $user = User::factory()->create();
    $patient = $user->patient;

    $task = PatientTask::factory()->for($patient)->create([
        'description' => 'Original description',
        'instructions' => 'Original instructions',
    ]);

    $this->actingAs($user)
        ->get(route('tasks.edit', $task))
        ->assertSuccessful()
        ->assertSee('Original description');

    $this->actingAs($user);
    Volt::test('tasks.edit', ['taskId' => $task->id])
        ->set('description', 'Updated description')
        ->set('instructions', 'Updated instructions')
        ->call('update')
        ->assertRedirect(route('tasks.show', $task));

    $task->refresh();
    expect($task->description)->toBe('Updated description');
    expect($task->instructions)->toBe('Updated instructions');
});

test('user can edit task and change appointment link', function () {
    $user = User::factory()->create();
    $patient = $user->patient;

    $appointment1 = PatientAppointment::factory()->for($patient)->create();
    $appointment2 = PatientAppointment::factory()->for($patient)->create();

    $task = PatientTask::factory()->for($patient)->create([
        'description' => 'Test task',
        'patient_appointment_id' => $appointment1->id,
    ]);

    $this->actingAs($user);
    Volt::test('tasks.edit', ['taskId' => $task->id])
        ->set('patient_appointment_id', $appointment2->id)
        ->call('update')
        ->assertRedirect(route('tasks.show', $task));

    $task->refresh();
    expect($task->patient_appointment_id)->toBe($appointment2->id);
});

test('user can unlink appointment from task', function () {
    $user = User::factory()->create();
    $patient = $user->patient;

    $appointment = PatientAppointment::factory()->for($patient)->create();

    $task = PatientTask::factory()->for($patient)->create([
        'description' => 'Test task',
        'patient_appointment_id' => $appointment->id,
    ]);

    $this->actingAs($user);
    Volt::test('tasks.edit', ['taskId' => $task->id])
        ->set('patient_appointment_id', '')
        ->call('update')
        ->assertRedirect(route('tasks.show', $task));

    $task->refresh();
    expect($task->patient_appointment_id)->toBeNull();
});

test('user cannot edit another users task', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $patient2 = $user2->patient;

    $task = PatientTask::factory()->for($patient2)->create([
        'description' => 'User 2 task',
    ]);

    $this->actingAs($user1)
        ->get(route('tasks.edit', $task))
        ->assertForbidden();
});

test('user can delete task', function () {
    $user = User::factory()->create();
    $patient = $user->patient;

    $task = PatientTask::factory()->for($patient)->create([
        'description' => 'Task to delete',
    ]);

    $this->actingAs($user);
    Volt::test('tasks.show', ['taskId' => $task->id])
        ->call('deleteTask')
        ->assertRedirect(route('tasks.index'));

    expect(PatientTask::find($task->id))->toBeNull();
});

test('user cannot delete another users task', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $patient2 = $user2->patient;

    $task = PatientTask::factory()->for($patient2)->create([
        'description' => 'User 2 task',
    ]);

    $this->actingAs($user1)
        ->get(route('tasks.show', $task))
        ->assertForbidden();
});

test('task shows scheduled appointment when linked', function () {
    $user = User::factory()->create();
    $patient = $user->patient;

    $task = PatientTask::factory()->for($patient)->create([
        'description' => 'Schedule MRI',
        'is_scheduling_task' => true,
    ]);

    $system = HealthcareSystem::factory()->create(['name' => 'Test Healthcare System']);
    $provider = HealthcareProvider::factory()->for($system, 'system')->create(['name' => 'Dr. Scheduled']);

    $scheduledAppointment = PatientAppointment::factory()->for($patient)->create([
        'healthcare_provider_id' => $provider->id,
        'scheduled_from_task_id' => $task->id,
    ]);

    $this->actingAs($user);
    Volt::test('tasks.show', ['taskId' => $task->id])
        ->assertSee('Scheduled Appointment')
        ->assertSee('Dr. Scheduled');
});
