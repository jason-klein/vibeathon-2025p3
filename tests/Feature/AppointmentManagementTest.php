<?php

use App\Models\HealthcareProvider;
use App\Models\HealthcareSystem;
use App\Models\Patient;
use App\Models\PatientAppointment;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Volt\Volt;

test('guests cannot access appointments index', function () {
    $this->get('/appointments')->assertRedirect('/login');
});

test('authenticated users can view appointments index', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/appointments')
        ->assertSuccessful()
        ->assertSee('My Appointments');
});

test('user can view all their appointments', function () {
    $user = User::factory()->create();
    $patient = $user->patient;

    $system = HealthcareSystem::factory()->create(['name' => 'Test Healthcare System']);
    $provider = HealthcareProvider::factory()->for($system, 'system')->create([
        'name' => 'Dr. Sarah Johnson',
    ]);

    // Create past and future appointments
    PatientAppointment::factory()->for($patient)->create([
        'healthcare_provider_id' => $provider->id,
        'date' => now()->subDays(7),
        'summary' => 'Past checkup',
    ]);

    PatientAppointment::factory()->for($patient)->create([
        'healthcare_provider_id' => $provider->id,
        'date' => now()->addDays(7),
        'summary' => 'Upcoming appointment',
    ]);

    $response = $this->actingAs($user)->get('/appointments');

    $response->assertSuccessful();
    $response->assertSee('Dr. Sarah Johnson');
    // Only upcoming is shown by default
    $response->assertSee('Upcoming appointment');
});

test('appointments are filtered correctly by upcoming and past', function () {
    $user = User::factory()->create();
    $patient = $user->patient;

    $system = HealthcareSystem::factory()->create(['name' => 'Test Healthcare System']);
    $provider = HealthcareProvider::factory()->for($system, 'system')->create();

    // Create past appointment
    $pastAppointment = PatientAppointment::factory()->for($patient)->create([
        'healthcare_provider_id' => $provider->id,
        'date' => now()->subDays(7),
        'summary' => 'This is a past appointment',
    ]);

    // Create future appointment
    $futureAppointment = PatientAppointment::factory()->for($patient)->create([
        'healthcare_provider_id' => $provider->id,
        'date' => now()->addDays(7),
        'summary' => 'This is an upcoming appointment',
    ]);

    // Test upcoming filter (default)
    $this->actingAs($user);
    Volt::test('appointments.index')
        ->assertSee('This is an upcoming appointment')
        ->assertDontSee('This is a past appointment');

    // Test past filter
    $this->actingAs($user);
    Volt::test('appointments.index')
        ->set('filter', 'past')
        ->assertSee('This is a past appointment')
        ->assertDontSee('This is an upcoming appointment');

    // Test all filter
    $this->actingAs($user);
    Volt::test('appointments.index')
        ->set('filter', 'all')
        ->assertSee('This is a past appointment')
        ->assertSee('This is an upcoming appointment');
});

test('user can only see their own appointments', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $patient1 = $user1->patient;
    $patient2 = $user2->patient;

    $system = HealthcareSystem::factory()->create(['name' => 'Test Healthcare System']);
    $provider = HealthcareProvider::factory()->for($system, 'system')->create();

    // Create appointments for both users
    PatientAppointment::factory()->for($patient1)->create([
        'healthcare_provider_id' => $provider->id,
        'summary' => 'User 1 appointment',
    ]);

    PatientAppointment::factory()->for($patient2)->create([
        'healthcare_provider_id' => $provider->id,
        'summary' => 'User 2 appointment',
    ]);

    // User 1 should only see their appointment
    $response = $this->actingAs($user1)->get('/appointments');
    $response->assertSee('User 1 appointment');
    $response->assertDontSee('User 2 appointment');

    // User 2 should only see their appointment
    $response = $this->actingAs($user2)->get('/appointments');
    $response->assertSee('User 2 appointment');
    $response->assertDontSee('User 1 appointment');
});

test('appointments index shows empty state when no appointments exist', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/appointments');

    $response->assertSuccessful();
    $response->assertSee('No upcoming appointments');
});

test('authenticated users can access appointment create page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/appointments/create')
        ->assertSuccessful()
        ->assertSee('Add Appointment');
});

test('user can create a future appointment with minimal fields', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $patient = $user->patient;

    $system = HealthcareSystem::factory()->create(['name' => 'Test Healthcare System']);
    $provider = HealthcareProvider::factory()->for($system, 'system')->create([
        'name' => 'Dr. Michael Chen',
    ]);

    $this->actingAs($user);
    Volt::test('appointments.create')
        ->set('date', now()->addDays(7)->format('Y-m-d'))
        ->set('time', '14:30')
        ->set('healthcare_provider_id', $provider->id)
        ->set('location', '123 Medical Plaza')
        ->set('summary', 'Annual checkup')
        ->call('save')
        ->assertRedirect(route('appointments.index'));

    $this->assertDatabaseHas('patient_appointments', [
        'patient_id' => $patient->id,
        'healthcare_provider_id' => $provider->id,
        'location' => '123 Medical Plaza',
        'summary' => 'Annual checkup',
    ]);
});

test('past appointments require provider and notes', function () {
    $user = User::factory()->create();

    // Try to create past appointment without required fields
    $this->actingAs($user);
    Volt::test('appointments.create')
        ->set('date', now()->subDays(7)->format('Y-m-d'))
        ->set('time', '10:00')
        ->call('save')
        ->assertHasErrors(['healthcare_provider_id', 'patient_notes']);
});

test('user can create a past appointment with required fields', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $patient = $user->patient;

    $system = HealthcareSystem::factory()->create(['name' => 'Test Healthcare System']);
    $provider = HealthcareProvider::factory()->for($system, 'system')->create([
        'name' => 'Dr. Emily Wilson',
    ]);

    $this->actingAs($user);
    Volt::test('appointments.create')
        ->set('date', now()->subDays(7)->format('Y-m-d'))
        ->set('time', '10:00')
        ->set('healthcare_provider_id', $provider->id)
        ->set('location', '456 Health Center')
        ->set('summary', 'Follow-up visit')
        ->set('patient_notes', 'Discussed treatment options and next steps.')
        ->call('save')
        ->assertRedirect(route('appointments.index'));

    $this->assertDatabaseHas('patient_appointments', [
        'patient_id' => $patient->id,
        'healthcare_provider_id' => $provider->id,
        'patient_notes' => 'Discussed treatment options and next steps.',
    ]);
});

test('user can upload documents when creating an appointment', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $patient = $user->patient;

    $system = HealthcareSystem::factory()->create(['name' => 'Test Healthcare System']);
    $provider = HealthcareProvider::factory()->for($system, 'system')->create();

    $file = UploadedFile::fake()->create('test-document.pdf', 1000, 'application/pdf');

    $this->actingAs($user);
    Volt::test('appointments.create')
        ->set('date', now()->addDays(7)->format('Y-m-d'))
        ->set('healthcare_provider_id', $provider->id)
        ->set('documents', [$file])
        ->call('save')
        ->assertRedirect(route('appointments.index'));

    // Verify appointment was created
    $appointment = PatientAppointment::where('patient_id', $patient->id)->first();
    expect($appointment)->not->toBeNull();

    // Verify document was uploaded and associated with appointment
    expect($appointment->documents)->toHaveCount(1);
    Storage::disk('public')->assertExists($appointment->documents->first()->file_path);
});

test('document upload validation rejects invalid file types', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $system = HealthcareSystem::factory()->create(['name' => 'Test Healthcare System']);
    $provider = HealthcareProvider::factory()->for($system, 'system')->create();

    $invalidFile = UploadedFile::fake()->create('test.exe', 1000);

    $this->actingAs($user);
    Volt::test('appointments.create')
        ->set('date', now()->addDays(7)->format('Y-m-d'))
        ->set('healthcare_provider_id', $provider->id)
        ->set('documents', [$invalidFile])
        ->call('save')
        ->assertHasErrors(['documents.*']);
});

test('validation errors display properly for required fields', function () {
    $user = User::factory()->create();

    $this->actingAs($user);
    Volt::test('appointments.create')
        ->call('save')
        ->assertHasErrors(['date']);
});

test('user cannot create appointment for another patient', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $patient2 = $user2->patient;

    $system = HealthcareSystem::factory()->create(['name' => 'Test Healthcare System']);
    $provider = HealthcareProvider::factory()->for($system, 'system')->create();

    // User 1 tries to create appointment for patient 2
    $this->actingAs($user1)
        ->post('/appointments', [
            'patient_id' => $patient2->id,
            'date' => now()->addDays(7)->format('Y-m-d'),
            'healthcare_provider_id' => $provider->id,
        ]);

    // Verify no appointment was created for patient 2
    $this->assertDatabaseMissing('patient_appointments', [
        'patient_id' => $patient2->id,
    ]);
});

test('appointments display with documents and tasks count', function () {
    $user = User::factory()->create();
    $patient = $user->patient;

    $system = HealthcareSystem::factory()->create(['name' => 'Test Healthcare System']);
    $provider = HealthcareProvider::factory()->for($system, 'system')->create();

    $appointment = PatientAppointment::factory()->for($patient)->create([
        'healthcare_provider_id' => $provider->id,
        'date' => now()->addDays(7),
    ]);

    // Add documents and tasks
    $appointment->documents()->create([
        'file_path' => 'test/path.pdf',
        'summary' => 'Test document',
    ]);

    $appointment->tasks()->create([
        'patient_id' => $patient->id,
        'description' => 'Follow up task',
    ]);

    $response = $this->actingAs($user)->get('/appointments');

    $response->assertSee('1 document');
    $response->assertSee('1 task');
});

test('appointment shows today badge for appointments scheduled today', function () {
    $user = User::factory()->create();
    $patient = $user->patient;

    $system = HealthcareSystem::factory()->create(['name' => 'Test Healthcare System']);
    $provider = HealthcareProvider::factory()->for($system, 'system')->create();

    PatientAppointment::factory()->for($patient)->create([
        'healthcare_provider_id' => $provider->id,
        'date' => today(),
        'time' => '14:00',
    ]);

    $response = $this->actingAs($user)->get('/appointments');

    $response->assertSee('Today');
});

// === APPOINTMENT SHOW/VIEW TESTS ===

test('user can view appointment details', function () {
    $user = User::factory()->create();
    $patient = $user->patient;

    $system = HealthcareSystem::factory()->create(['name' => 'Freeman Health System']);
    $provider = HealthcareProvider::factory()->for($system, 'system')->create([
        'name' => 'Dr. Sarah Johnson',
        'specialty' => 'Cardiology',
    ]);

    $appointment = PatientAppointment::factory()->for($patient)->create([
        'healthcare_provider_id' => $provider->id,
        'date' => now()->addDays(7),
        'time' => '14:30',
        'location' => '123 Medical Plaza',
        'summary' => 'Annual checkup',
        'patient_notes' => 'Feeling good overall',
    ]);

    $response = $this->actingAs($user)->get("/appointments/{$appointment->id}");

    $response->assertSuccessful();
    $response->assertSee('Dr. Sarah Johnson');
    $response->assertSee('Cardiology');
    $response->assertSee('Freeman Health System');
    $response->assertSee('123 Medical Plaza');
    $response->assertSee('Annual checkup');
    $response->assertSee('Feeling good overall');
});

test('related tasks display correctly on appointment page', function () {
    $user = User::factory()->create();
    $patient = $user->patient;

    $system = HealthcareSystem::factory()->create(['name' => 'Test Healthcare System']);
    $provider = HealthcareProvider::factory()->for($system, 'system')->create();

    $appointment = PatientAppointment::factory()->for($patient)->create([
        'healthcare_provider_id' => $provider->id,
        'date' => now()->addDays(7),
    ]);

    // Add tasks to the appointment
    $appointment->tasks()->create([
        'patient_id' => $patient->id,
        'description' => 'Schedule follow-up MRI',
        'instructions' => 'Call radiology department',
        'is_scheduling_task' => false,
    ]);

    $appointment->tasks()->create([
        'patient_id' => $patient->id,
        'description' => 'Pick up prescription',
        'completed_at' => now(),
    ]);

    $response = $this->actingAs($user)->get("/appointments/{$appointment->id}");

    $response->assertSuccessful();
    $response->assertSee('Schedule follow-up MRI');
    $response->assertSee('Pick up prescription');
    $response->assertSee('Call radiology department');
});

test('user can add new task linked to appointment', function () {
    $user = User::factory()->create();
    $patient = $user->patient;

    $system = HealthcareSystem::factory()->create(['name' => 'Test Healthcare System']);
    $provider = HealthcareProvider::factory()->for($system, 'system')->create();

    $appointment = PatientAppointment::factory()->for($patient)->create([
        'healthcare_provider_id' => $provider->id,
        'date' => now()->addDays(7),
    ]);

    $this->actingAs($user);
    Volt::test('appointments.show', ['appointmentId' => $appointment->id])
        ->set('newTaskDescription', 'Get blood work done')
        ->set('newTaskInstructions', 'Fast for 12 hours before test')
        ->call('addTask')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('patient_tasks', [
        'patient_id' => $patient->id,
        'patient_appointment_id' => $appointment->id,
        'description' => 'Get blood work done',
        'instructions' => 'Fast for 12 hours before test',
    ]);
});

test('user can mark tasks as complete from appointment page', function () {
    $user = User::factory()->create();
    $patient = $user->patient;

    $system = HealthcareSystem::factory()->create(['name' => 'Test Healthcare System']);
    $provider = HealthcareProvider::factory()->for($system, 'system')->create();

    $appointment = PatientAppointment::factory()->for($patient)->create([
        'healthcare_provider_id' => $provider->id,
        'date' => now()->addDays(7),
    ]);

    $task = $appointment->tasks()->create([
        'patient_id' => $patient->id,
        'description' => 'Complete paperwork',
    ]);

    expect($task->completed_at)->toBeNull();

    $this->actingAs($user);
    Volt::test('appointments.show', ['appointmentId' => $appointment->id])
        ->call('toggleTask', $task->id);

    $task->refresh();
    expect($task->completed_at)->not->toBeNull();
});

test('user can mark tasks as incomplete from appointment page', function () {
    $user = User::factory()->create();
    $patient = $user->patient;

    $system = HealthcareSystem::factory()->create(['name' => 'Test Healthcare System']);
    $provider = HealthcareProvider::factory()->for($system, 'system')->create();

    $appointment = PatientAppointment::factory()->for($patient)->create([
        'healthcare_provider_id' => $provider->id,
        'date' => now()->addDays(7),
    ]);

    $task = $appointment->tasks()->create([
        'patient_id' => $patient->id,
        'description' => 'Complete paperwork',
        'completed_at' => now(),
    ]);

    expect($task->completed_at)->not->toBeNull();

    $this->actingAs($user);
    Volt::test('appointments.show', ['appointmentId' => $appointment->id])
        ->call('toggleTask', $task->id);

    $task->refresh();
    expect($task->completed_at)->toBeNull();
});

test('user can edit patient notes in-place', function () {
    $user = User::factory()->create();
    $patient = $user->patient;

    $system = HealthcareSystem::factory()->create(['name' => 'Test Healthcare System']);
    $provider = HealthcareProvider::factory()->for($system, 'system')->create();

    $appointment = PatientAppointment::factory()->for($patient)->create([
        'healthcare_provider_id' => $provider->id,
        'date' => now()->addDays(7),
        'patient_notes' => 'Original notes',
    ]);

    $this->actingAs($user);
    Volt::test('appointments.show', ['appointmentId' => $appointment->id])
        ->call('toggleEditNotes')
        ->assertSet('editingNotes', true)
        ->set('patientNotes', 'Updated notes with more details')
        ->call('saveNotes')
        ->assertHasNoErrors();

    $appointment->refresh();
    expect($appointment->patient_notes)->toBe('Updated notes with more details');
});

test('documents display and can be downloaded', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $patient = $user->patient;

    $system = HealthcareSystem::factory()->create(['name' => 'Test Healthcare System']);
    $provider = HealthcareProvider::factory()->for($system, 'system')->create();

    $appointment = PatientAppointment::factory()->for($patient)->create([
        'healthcare_provider_id' => $provider->id,
        'date' => now()->addDays(7),
    ]);

    // Create a fake file
    Storage::disk('public')->put('test-document.pdf', 'test content');

    $document = $appointment->documents()->create([
        'file_path' => 'test-document.pdf',
        'summary' => 'Lab results',
    ]);

    // Test that document is shown
    $response = $this->actingAs($user)->get("/appointments/{$appointment->id}");
    $response->assertSuccessful();
    $response->assertSee('test-document.pdf');

    // Test download
    $response = $this->actingAs($user)->get("/appointments/{$appointment->id}/documents/{$document->id}/download");
    $response->assertSuccessful();
    $response->assertDownload('test-document.pdf');
});

test('user cannot view another users appointment', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $patient2 = $user2->patient;

    $system = HealthcareSystem::factory()->create(['name' => 'Test Healthcare System']);
    $provider = HealthcareProvider::factory()->for($system, 'system')->create();

    $appointment = PatientAppointment::factory()->for($patient2)->create([
        'healthcare_provider_id' => $provider->id,
        'date' => now()->addDays(7),
    ]);

    $response = $this->actingAs($user1)->get("/appointments/{$appointment->id}");

    $response->assertForbidden();
});

// === APPOINTMENT EDIT TESTS ===

test('user can edit existing appointment', function () {
    $user = User::factory()->create();
    $patient = $user->patient;

    $system = HealthcareSystem::factory()->create(['name' => 'Test Healthcare System']);
    $provider = HealthcareProvider::factory()->for($system, 'system')->create([
        'name' => 'Dr. Original Provider',
    ]);

    $newProvider = HealthcareProvider::factory()->for($system, 'system')->create([
        'name' => 'Dr. New Provider',
    ]);

    $appointment = PatientAppointment::factory()->for($patient)->create([
        'healthcare_provider_id' => $provider->id,
        'date' => now()->addDays(7),
        'time' => '10:00',
        'location' => 'Original Location',
        'summary' => 'Original summary',
        'patient_notes' => 'Original notes',
    ]);

    $this->actingAs($user);
    Volt::test('appointments.edit', ['appointmentId' => $appointment->id])
        ->assertSet('date', $appointment->date->format('Y-m-d'))
        ->set('time', '14:30')
        ->set('healthcare_provider_id', $newProvider->id)
        ->set('location', 'New Location')
        ->set('summary', 'Updated summary')
        ->set('patient_notes', 'Updated notes')
        ->call('update')
        ->assertRedirect(route('appointments.show', $appointment));

    $appointment->refresh();
    expect($appointment->healthcare_provider_id)->toBe($newProvider->id);
    expect($appointment->location)->toBe('New Location');
    expect($appointment->summary)->toBe('Updated summary');
    expect($appointment->patient_notes)->toBe('Updated notes');
});

test('user can remove existing documents when editing appointment', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $patient = $user->patient;

    $system = HealthcareSystem::factory()->create(['name' => 'Test Healthcare System']);
    $provider = HealthcareProvider::factory()->for($system, 'system')->create();

    $appointment = PatientAppointment::factory()->for($patient)->create([
        'healthcare_provider_id' => $provider->id,
        'date' => now()->addDays(7),
    ]);

    Storage::disk('public')->put('old-document.pdf', 'old content');
    $document = $appointment->documents()->create([
        'file_path' => 'old-document.pdf',
    ]);

    $this->actingAs($user);
    Volt::test('appointments.edit', ['appointmentId' => $appointment->id])
        ->call('removeExistingDocument', $document->id)
        ->call('update')
        ->assertRedirect(route('appointments.show', $appointment));

    $this->assertDatabaseMissing('patient_appointment_documents', [
        'id' => $document->id,
    ]);

    Storage::disk('public')->assertMissing('old-document.pdf');
});

test('user cannot edit another users appointment', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $patient2 = $user2->patient;

    $system = HealthcareSystem::factory()->create(['name' => 'Test Healthcare System']);
    $provider = HealthcareProvider::factory()->for($system, 'system')->create();

    $appointment = PatientAppointment::factory()->for($patient2)->create([
        'healthcare_provider_id' => $provider->id,
        'date' => now()->addDays(7),
    ]);

    $response = $this->actingAs($user1)->get("/appointments/{$appointment->id}/edit");

    $response->assertForbidden();
});

// === APPOINTMENT DELETE TESTS ===

test('user can delete appointment with confirmation', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $patient = $user->patient;

    $system = HealthcareSystem::factory()->create(['name' => 'Test Healthcare System']);
    $provider = HealthcareProvider::factory()->for($system, 'system')->create();

    $appointment = PatientAppointment::factory()->for($patient)->create([
        'healthcare_provider_id' => $provider->id,
        'date' => now()->addDays(7),
    ]);

    // Add a document that should be deleted
    Storage::disk('public')->put('document-to-delete.pdf', 'content');
    $appointment->documents()->create([
        'file_path' => 'document-to-delete.pdf',
    ]);

    $this->actingAs($user);
    Volt::test('appointments.show', ['appointmentId' => $appointment->id])
        ->call('deleteAppointment')
        ->assertRedirect(route('appointments.index'));

    $this->assertDatabaseMissing('patient_appointments', [
        'id' => $appointment->id,
    ]);

    Storage::disk('public')->assertMissing('document-to-delete.pdf');
});

test('user cannot delete another users appointment', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $patient2 = $user2->patient;

    $system = HealthcareSystem::factory()->create(['name' => 'Test Healthcare System']);
    $provider = HealthcareProvider::factory()->for($system, 'system')->create();

    $appointment = PatientAppointment::factory()->for($patient2)->create([
        'healthcare_provider_id' => $provider->id,
        'date' => now()->addDays(7),
    ]);

    // User 1 tries to access User 2's appointment - should be forbidden
    $response = $this->actingAs($user1)->get("/appointments/{$appointment->id}");
    $response->assertForbidden();

    // Verify appointment still exists
    $this->assertDatabaseHas('patient_appointments', [
        'id' => $appointment->id,
    ]);
});

test('appointment cards are clickable and link to details page', function () {
    $user = User::factory()->create();
    $patient = $user->patient;

    $system = HealthcareSystem::factory()->create(['name' => 'Test Healthcare System']);
    $provider = HealthcareProvider::factory()->for($system, 'system')->create([
        'name' => 'Dr. Test Provider',
    ]);

    $appointment = PatientAppointment::factory()->for($patient)->create([
        'healthcare_provider_id' => $provider->id,
        'date' => now()->addDays(7),
    ]);

    $response = $this->actingAs($user)->get('/appointments');

    $response->assertSuccessful();
    $response->assertSee('href="'.route('appointments.show', $appointment).'"', false);
});
