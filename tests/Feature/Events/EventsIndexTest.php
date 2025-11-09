<?php

declare(strict_types=1);

use App\Models\CommunityEvent;
use App\Models\CommunityPartner;
use App\Models\Patient;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

// Authentication & Authorization
test('events index requires authentication', function () {
    get(route('events.index'))
        ->assertRedirect(route('login'));
});

test('authenticated user can view events index', function () {
    $user = User::factory()->create();

    actingAs($user)
        ->get(route('events.index'))
        ->assertSuccessful()
        ->assertSee('Community Events');
});

// Basic Display
test('events index displays upcoming events', function () {
    $user = User::factory()->create();
    $partner = CommunityPartner::factory()->create(['name' => 'Test Health Center']);

    // Create upcoming event
    $upcomingEvent = CommunityEvent::factory()->create([
        'community_partner_id' => $partner->id,
        'date' => today()->addDays(7),
        'description' => 'Upcoming health screening',
    ]);

    actingAs($user)
        ->get(route('events.index'))
        ->assertSuccessful()
        ->assertSee('Test Health Center')
        ->assertSee('Upcoming health screening');
});

test('events index does not display past events by default', function () {
    $user = User::factory()->create();
    $partner = CommunityPartner::factory()->create(['name' => 'Past Event Partner']);

    // Create past event
    CommunityEvent::factory()->create([
        'community_partner_id' => $partner->id,
        'date' => today()->subDays(7),
        'description' => 'Past health screening',
    ]);

    actingAs($user)
        ->get(route('events.index'))
        ->assertSuccessful()
        ->assertDontSee('Past Event Partner')
        ->assertDontSee('Past health screening');
});

test('events index shows event count', function () {
    $user = User::factory()->create();
    $partner = CommunityPartner::factory()->create();

    CommunityEvent::factory()->count(3)->create([
        'community_partner_id' => $partner->id,
        'date' => today()->addDays(7),
    ]);

    actingAs($user)
        ->get(route('events.index'))
        ->assertSuccessful()
        ->assertSee('3 events');
});

test('events index shows empty state when no events', function () {
    $user = User::factory()->create();

    actingAs($user)
        ->get(route('events.index'))
        ->assertSuccessful()
        ->assertSee('No events found')
        ->assertSee('Check back soon for upcoming community events');
});

// Keyword Search
test('keyword filter searches event descriptions', function () {
    $user = User::factory()->create();
    $partner = CommunityPartner::factory()->create();

    CommunityEvent::factory()->create([
        'community_partner_id' => $partner->id,
        'date' => today()->addDays(7),
        'description' => 'Diabetes screening and education',
    ]);

    CommunityEvent::factory()->create([
        'community_partner_id' => $partner->id,
        'date' => today()->addDays(14),
        'description' => 'Heart health workshop',
    ]);

    actingAs($user)
        ->get(route('events.index', ['keyword' => 'diabetes']))
        ->assertSuccessful()
        ->assertSee('Diabetes screening')
        ->assertDontSee('Heart health workshop');
});

test('keyword filter searches partner names', function () {
    $user = User::factory()->create();

    $partner1 = CommunityPartner::factory()->create(['name' => 'Diabetes Foundation']);
    $partner2 = CommunityPartner::factory()->create(['name' => 'Heart Health Center']);

    CommunityEvent::factory()->create([
        'community_partner_id' => $partner1->id,
        'date' => today()->addDays(7),
        'description' => 'Health screening',
    ]);

    CommunityEvent::factory()->create([
        'community_partner_id' => $partner2->id,
        'date' => today()->addDays(14),
        'description' => 'Wellness workshop',
    ]);

    actingAs($user)
        ->get(route('events.index', ['keyword' => 'Diabetes']))
        ->assertSuccessful()
        ->assertSee('Diabetes Foundation')
        ->assertDontSee('Heart Health Center');
});

test('keyword filter searches locations', function () {
    $user = User::factory()->create();
    $partner = CommunityPartner::factory()->create();

    CommunityEvent::factory()->create([
        'community_partner_id' => $partner->id,
        'date' => today()->addDays(7),
        'location' => 'Community Center Downtown',
        'description' => 'Health screening',
    ]);

    CommunityEvent::factory()->create([
        'community_partner_id' => $partner->id,
        'date' => today()->addDays(14),
        'location' => 'Library Branch West',
        'description' => 'Wellness workshop',
    ]);

    actingAs($user)
        ->get(route('events.index', ['keyword' => 'Downtown']))
        ->assertSuccessful()
        ->assertSee('Community Center Downtown')
        ->assertDontSee('Library Branch West');
});

test('keyword filter is case insensitive', function () {
    $user = User::factory()->create();
    $partner = CommunityPartner::factory()->create();

    CommunityEvent::factory()->create([
        'community_partner_id' => $partner->id,
        'date' => today()->addDays(7),
        'description' => 'DIABETES screening',
    ]);

    actingAs($user)
        ->get(route('events.index', ['keyword' => 'diabetes']))
        ->assertSuccessful()
        ->assertSee('DIABETES screening');
});

// Date Range Filtering
test('start date filter limits events correctly', function () {
    $user = User::factory()->create();
    $partner = CommunityPartner::factory()->create(['name' => 'Test Partner']);

    $nearEvent = CommunityEvent::factory()->create([
        'community_partner_id' => $partner->id,
        'date' => today()->addDays(3),
        'description' => 'Near event',
    ]);

    $farEvent = CommunityEvent::factory()->create([
        'community_partner_id' => $partner->id,
        'date' => today()->addDays(30),
        'description' => 'Far event',
    ]);

    actingAs($user)
        ->get(route('events.index', ['startDate' => today()->addDays(15)->format('Y-m-d')]))
        ->assertSuccessful()
        ->assertDontSee('Near event')
        ->assertSee('Far event');
});

test('end date filter limits events correctly', function () {
    $user = User::factory()->create();
    $partner = CommunityPartner::factory()->create();

    $nearEvent = CommunityEvent::factory()->create([
        'community_partner_id' => $partner->id,
        'date' => today()->addDays(3),
        'description' => 'Near event',
    ]);

    $farEvent = CommunityEvent::factory()->create([
        'community_partner_id' => $partner->id,
        'date' => today()->addDays(30),
        'description' => 'Far event',
    ]);

    actingAs($user)
        ->get(route('events.index', ['endDate' => today()->addDays(15)->format('Y-m-d')]))
        ->assertSuccessful()
        ->assertSee('Near event')
        ->assertDontSee('Far event');
});

test('date range filter works together', function () {
    $user = User::factory()->create();
    $partner = CommunityPartner::factory()->create();

    $beforeEvent = CommunityEvent::factory()->create([
        'community_partner_id' => $partner->id,
        'date' => today()->addDays(5),
        'description' => 'Before range',
    ]);

    $inRangeEvent = CommunityEvent::factory()->create([
        'community_partner_id' => $partner->id,
        'date' => today()->addDays(15),
        'description' => 'In range',
    ]);

    $afterEvent = CommunityEvent::factory()->create([
        'community_partner_id' => $partner->id,
        'date' => today()->addDays(35),
        'description' => 'After range',
    ]);

    actingAs($user)
        ->get(route('events.index', [
            'startDate' => today()->addDays(10)->format('Y-m-d'),
            'endDate' => today()->addDays(20)->format('Y-m-d'),
        ]))
        ->assertSuccessful()
        ->assertDontSee('Before range')
        ->assertSee('In range')
        ->assertDontSee('After range');
});

// Distance Filtering
test('distance filter limits events within radius when patient has coordinates', function () {
    $user = User::factory()->create();

    // Create patient with Joplin, MO coordinates
    $patient = Patient::factory()->create([
        'user_id' => $user->id,
        'latitude' => 37.0842,
        'longitude' => -94.5133,
    ]);

    $partner = CommunityPartner::factory()->create();

    // Near event (within 5 miles)
    $nearEvent = CommunityEvent::factory()->create([
        'community_partner_id' => $partner->id,
        'date' => today()->addDays(7),
        'description' => 'Near event within radius',
        'latitude' => 37.0950, // ~0.7 miles north
        'longitude' => -94.5133,
    ]);

    // Far event (beyond 5 miles)
    $farEvent = CommunityEvent::factory()->create([
        'community_partner_id' => $partner->id,
        'date' => today()->addDays(14),
        'description' => 'Far event beyond radius',
        'latitude' => 37.2842, // ~13+ miles north
        'longitude' => -94.5133,
    ]);

    // Without distance filter, both should show
    actingAs($user)
        ->get(route('events.index'))
        ->assertSuccessful()
        ->assertSee('Near event within radius')
        ->assertSee('Far event beyond radius');
});

test('distance filter includes events without coordinates', function () {
    $user = User::factory()->create();

    $patient = Patient::factory()->create([
        'user_id' => $user->id,
        'latitude' => 37.0842,
        'longitude' => -94.5133,
    ]);

    $partner = CommunityPartner::factory()->create();

    $eventWithoutCoords = CommunityEvent::factory()->create([
        'community_partner_id' => $partner->id,
        'date' => today()->addDays(7),
        'description' => 'Event without coordinates',
        'latitude' => null,
        'longitude' => null,
    ]);

    actingAs($user)
        ->get(route('events.index', ['maxDistance' => 5]))
        ->assertSuccessful()
        ->assertSee('Event without coordinates');
});

test('events display correctly for patient without coordinates', function () {
    $user = User::factory()->create();

    // Patient without coordinates
    $patient = Patient::factory()->create([
        'user_id' => $user->id,
        'latitude' => null,
        'longitude' => null,
    ]);

    $partner = CommunityPartner::factory()->create();

    $event = CommunityEvent::factory()->create([
        'community_partner_id' => $partner->id,
        'date' => today()->addDays(7),
        'description' => 'Event for patient without location',
        'latitude' => 40.0000,
        'longitude' => -100.0000,
    ]);

    actingAs($user)
        ->get(route('events.index'))
        ->assertSuccessful()
        ->assertSee('Event for patient without location');
});

// Navigation
test('sidebar link is highlighted on events index', function () {
    $user = User::factory()->create();

    actingAs($user)
        ->get(route('events.index'))
        ->assertSuccessful()
        ->assertSee('Community Events');
});

test('clicking event card navigates to event details', function () {
    $user = User::factory()->create();
    $partner = CommunityPartner::factory()->create(['name' => 'Test Partner']);

    $event = CommunityEvent::factory()->create([
        'community_partner_id' => $partner->id,
        'date' => today()->addDays(7),
    ]);

    actingAs($user)
        ->get(route('events.index'))
        ->assertSuccessful()
        ->assertSee(route('events.show', $event->id));
});

// Empty State with Filters
test('empty state shows filter hint when filters are active', function () {
    $user = User::factory()->create();
    $partner = CommunityPartner::factory()->create();

    CommunityEvent::factory()->create([
        'community_partner_id' => $partner->id,
        'date' => today()->addDays(7),
        'description' => 'Health screening',
    ]);

    actingAs($user)
        ->get(route('events.index', ['keyword' => 'nonexistent']))
        ->assertSuccessful()
        ->assertSee('No events found')
        ->assertSee('Try adjusting your filters');
});

// Events with Nonprofit Badge
test('nonprofit partner events show nonprofit badge', function () {
    $user = User::factory()->create();

    $partner = CommunityPartner::factory()->create([
        'name' => 'Nonprofit Health Foundation',
        'is_nonprofit' => true,
    ]);

    CommunityEvent::factory()->create([
        'community_partner_id' => $partner->id,
        'date' => today()->addDays(7),
        'description' => 'Free health screening',
    ]);

    actingAs($user)
        ->get(route('events.index'))
        ->assertSuccessful()
        ->assertSee('Nonprofit Health Foundation')
        ->assertSee('Nonprofit');
});

// Events with Time Display
test('event cards show time when available', function () {
    $user = User::factory()->create();
    $partner = CommunityPartner::factory()->create();

    CommunityEvent::factory()->create([
        'community_partner_id' => $partner->id,
        'date' => today()->addDays(7),
        'time' => '14:00',
        'description' => 'Afternoon event',
    ]);

    actingAs($user)
        ->get(route('events.index'))
        ->assertSuccessful()
        ->assertSee('2:00 PM');
});

// Distance Display
test('event cards show distance when both patient and event have coordinates', function () {
    $user = User::factory()->create();

    $patient = Patient::factory()->create([
        'user_id' => $user->id,
        'latitude' => 37.0842,
        'longitude' => -94.5133,
    ]);

    $partner = CommunityPartner::factory()->create();

    CommunityEvent::factory()->create([
        'community_partner_id' => $partner->id,
        'date' => today()->addDays(7),
        'latitude' => 37.0950,
        'longitude' => -94.5133,
    ]);

    actingAs($user)
        ->get(route('events.index'))
        ->assertSuccessful()
        ->assertSee('mile');
});
