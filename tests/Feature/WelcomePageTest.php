<?php

test('public homepage displays correctly', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
});

test('homepage shows ReferHarmony branding and messaging', function () {
    $response = $this->get('/');

    $response->assertSee('ReferHarmony')
        ->assertSee('Bridging Care with Clarity and Confidence')
        ->assertSee('Your Healthcare Journey, Simplified')
        ->assertSee('Track all your healthcare appointments in one place')
        ->assertSee('Never miss important health tasks and follow-ups')
        ->assertSee('Discover community health events and resources near you')
        ->assertSee('Securely store appointment documents and notes');
});

test('homepage has registration link for guests', function () {
    $response = $this->get('/');

    $response->assertSee('Get Started Today')
        ->assertSee('Sign In');
});

test('homepage shows dashboard link for authenticated users', function () {
    $user = \App\Models\User::factory()->create();

    $response = $this->actingAs($user)->get('/');

    $response->assertSee('Dashboard');
});
