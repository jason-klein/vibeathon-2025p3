<?php

use App\Models\CommunityEvent;
use App\Models\CommunityPartner;
use App\Models\Patient;
use App\Models\PatientAppointment;
use App\Models\User;
use Livewire\Volt\Volt;

use function Pest\Laravel\actingAs;

test('user can view event details', function () {
    $user = User::factory()->create();
    $patient = Patient::factory()->for($user)->create();

    $partner = CommunityPartner::factory()->create([
        'name' => 'Diabetes Support Group',
        'is_nonprofit' => true,
        'is_sponsor' => false,
    ]);

    $event = CommunityEvent::factory()->for($partner, 'partner')->create([
        'date' => now()->addDays(7),
        'time' => '10:00:00',
        'location' => 'Community Center',
        'description' => 'Join us for a diabetes support group meeting.',
        'is_partner_provided' => true,
    ]);

    actingAs($user);

    Volt::test('events.show', ['eventId' => $event->id])
        ->assertSee('Diabetes Support Group')
        ->assertSee('Join us for a diabetes support group meeting.')
        ->assertSee('Community Center')
        ->assertSee('Nonprofit Organization')
        ->assertSee('Partner-Provided Event');
});

test('event details displays date and time correctly', function () {
    $user = User::factory()->create();
    Patient::factory()->for($user)->create();

    $partner = CommunityPartner::factory()->create();
    $event = CommunityEvent::factory()->for($partner, 'partner')->create([
        'date' => now()->addDays(10),
        'time' => '14:30:00',
    ]);

    actingAs($user);

    $response = $this->get(route('events.show', $event->id));

    $response->assertSuccessful()
        ->assertSee($event->date->format('l, F j, Y'))
        ->assertSee('2:30 PM');
});

test('event details displays partner information', function () {
    $user = User::factory()->create();
    Patient::factory()->for($user)->create();

    $partner = CommunityPartner::factory()->create([
        'name' => 'Heart Health Foundation',
        'is_nonprofit' => true,
        'is_sponsor' => true,
    ]);

    $event = CommunityEvent::factory()->for($partner, 'partner')->create();

    actingAs($user);

    $response = $this->get(route('events.show', $event->id));

    $response->assertSuccessful()
        ->assertSee('Heart Health Foundation')
        ->assertSee('Nonprofit Organization')
        ->assertSee('Community Sponsor');
});

test('event feed shows events matching patient health interests', function () {
    $user = User::factory()->create();
    $patient = Patient::factory()->for($user)->create();

    // Create past appointment with diabetes-related content
    PatientAppointment::factory()->for($patient)->create([
        'date' => now()->subDays(30),
        'summary' => 'Patient has diabetes and needs to manage blood sugar levels.',
    ]);

    // Create diabetes-related event
    $diabetesPartner = CommunityPartner::factory()->create(['name' => 'Diabetes Care Network']);
    $diabetesEvent = CommunityEvent::factory()->for($diabetesPartner, 'partner')->create([
        'date' => now()->addDays(7),
        'description' => 'Learn about diabetes management and healthy living.',
    ]);

    // Create unrelated event
    $generalPartner = CommunityPartner::factory()->create(['name' => 'General Wellness']);
    CommunityEvent::factory()->for($generalPartner, 'partner')->create([
        'date' => now()->addDays(14),
        'description' => 'General wellness seminar about exercise.',
    ]);

    actingAs($user);

    $response = $this->get(route('dashboard'));

    $response->assertSuccessful()
        ->assertSee('Diabetes Care Network');
});

test('event feed filters based on task keywords', function () {
    $user = User::factory()->create();
    $patient = Patient::factory()->for($user)->create();

    // Create task with cardiology keyword
    $patient->tasks()->create([
        'description' => 'Schedule cardiology appointment',
        'is_scheduling_task' => true,
        'provider_specialty_needed' => 'Cardiology',
    ]);

    // Create heart-related event
    $heartPartner = CommunityPartner::factory()->create(['name' => 'Heart Foundation']);
    CommunityEvent::factory()->for($heartPartner, 'partner')->create([
        'date' => now()->addDays(10),
        'description' => 'Heart health screening and cardiology consultation available.',
    ]);

    actingAs($user);

    $response = $this->get(route('dashboard'));

    $response->assertSuccessful()
        ->assertSee('Heart Foundation');
});

test('event details page has back to dashboard link', function () {
    $user = User::factory()->create();
    Patient::factory()->for($user)->create();

    $event = CommunityEvent::factory()
        ->for(CommunityPartner::factory(), 'partner')
        ->create();

    actingAs($user);

    $response = $this->get(route('events.show', $event->id));

    $response->assertSuccessful()
        ->assertSee('Back to Events')
        ->assertSee(route('events.index'));
});

test('event cards on dashboard are clickable', function () {
    $user = User::factory()->create();
    Patient::factory()->for($user)->create();

    $partner = CommunityPartner::factory()->create(['name' => 'Test Partner']);
    $event = CommunityEvent::factory()->for($partner, 'partner')->create([
        'date' => now()->addDays(5),
        'description' => 'Test event description',
    ]);

    actingAs($user);

    $response = $this->get(route('dashboard'));

    $response->assertSuccessful()
        ->assertSee(route('events.show', $event->id))
        ->assertSee('Test Partner');
});

test('event details shows link when available', function () {
    $user = User::factory()->create();
    Patient::factory()->for($user)->create();

    $partner = CommunityPartner::factory()->create(['name' => 'Test Organization']);
    $event = CommunityEvent::factory()->for($partner, 'partner')->create([
        'link' => 'https://example.com/event',
    ]);

    actingAs($user);

    $response = $this->get(route('events.show', $event->id));

    $response->assertSuccessful()
        ->assertSee('View Event Details')
        ->assertSee('https://example.com/event')
        ->assertDontSee('Contact Test Organization for more information');
});

test('event details shows contact message when no link', function () {
    $user = User::factory()->create();
    Patient::factory()->for($user)->create();

    $partner = CommunityPartner::factory()->create(['name' => 'Test Organization']);
    $event = CommunityEvent::factory()->for($partner, 'partner')->create([
        'link' => null,
    ]);

    actingAs($user);

    $response = $this->get(route('events.show', $event->id));

    $response->assertSuccessful()
        ->assertSee('Contact Test Organization for more information')
        ->assertDontSee('View Event Details');
});
