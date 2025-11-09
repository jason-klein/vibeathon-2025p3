<?php

use App\Models\CommunityEvent;
use App\Models\CommunityPartner;
use App\Models\Patient;
use App\Models\User;
use Livewire\Volt\Volt;

it('can render', function () {
    $user = User::factory()->create();
    $patient = Patient::factory()->for($user)->create();

    $partner = CommunityPartner::factory()->create();
    $event = CommunityEvent::factory()->for($partner, 'partner')->create();

    $this->actingAs($user);

    $component = Volt::test('events.show', ['eventId' => $event->id]);

    $component->assertOk();
});
