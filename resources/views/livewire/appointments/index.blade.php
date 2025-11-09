<?php

use Illuminate\Support\Facades\Auth;

use function Livewire\Volt\computed;
use function Livewire\Volt\layout;
use function Livewire\Volt\state;
use function Livewire\Volt\title;

layout('components.layouts.app');
title('Appointments');

state(['filter' => 'upcoming']);

$appointments = computed(function () {
    $patient = Auth::user()->patient;

    if (! $patient) {
        return collect();
    }

    $query = $patient->appointments()
        ->with(['provider.system', 'documents', 'tasks']);

    if ($this->filter === 'upcoming') {
        $query->where('date', '>=', today());
    } elseif ($this->filter === 'past') {
        $query->where('date', '<', today());
    }

    return $query->orderBy('date', $this->filter === 'upcoming' ? 'asc' : 'desc')
        ->orderBy('time', $this->filter === 'upcoming' ? 'asc' : 'desc')
        ->get();
});

?>

<div class="flex h-full w-full flex-1 flex-col gap-6">
        {{-- Header --}}
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-zinc-900 dark:text-zinc-100">My Appointments</h1>
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Manage your healthcare appointments</p>
            </div>
        </div>

        {{-- Filter Tabs --}}
        <div class="border-b border-zinc-200 dark:border-zinc-700">
            <nav class="-mb-px flex gap-6">
                <button
                    wire:click="$set('filter', 'upcoming')"
                    class="border-b-2 px-1 py-4 text-sm font-medium transition-colors {{ $filter === 'upcoming' ? 'border-blue-600 text-blue-600 dark:border-blue-400 dark:text-blue-400' : 'border-transparent text-zinc-600 hover:border-zinc-300 hover:text-zinc-900 dark:text-zinc-400 dark:hover:border-zinc-600 dark:hover:text-zinc-100' }}"
                >
                    Upcoming
                </button>
                <button
                    wire:click="$set('filter', 'past')"
                    class="border-b-2 px-1 py-4 text-sm font-medium transition-colors {{ $filter === 'past' ? 'border-blue-600 text-blue-600 dark:border-blue-400 dark:text-blue-400' : 'border-transparent text-zinc-600 hover:border-zinc-300 hover:text-zinc-900 dark:text-zinc-400 dark:hover:border-zinc-600 dark:hover:text-zinc-100' }}"
                >
                    Past
                </button>
                <button
                    wire:click="$set('filter', 'all')"
                    class="border-b-2 px-1 py-4 text-sm font-medium transition-colors {{ $filter === 'all' ? 'border-blue-600 text-blue-600 dark:border-blue-400 dark:text-blue-400' : 'border-transparent text-zinc-600 hover:border-zinc-300 hover:text-zinc-900 dark:text-zinc-400 dark:hover:border-zinc-600 dark:hover:text-zinc-100' }}"
                >
                    All
                </button>
            </nav>
        </div>

        {{-- Appointments List --}}
        <div class="space-y-4">
            @forelse($this->appointments as $appointment)
                <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center gap-3">
                                <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">
                                    {{ $appointment->provider?->name ?? 'No provider assigned' }}
                                </h3>
                                @if($appointment->date < today())
                                    <span class="inline-flex items-center rounded-full bg-zinc-100 px-2 py-1 text-xs font-medium text-zinc-700 dark:bg-zinc-700 dark:text-zinc-300">
                                        Past
                                    </span>
                                @elseif($appointment->date->isToday())
                                    <span class="inline-flex items-center rounded-full bg-green-100 px-2 py-1 text-xs font-medium text-green-700 dark:bg-green-900/30 dark:text-green-400">
                                        Today
                                    </span>
                                @endif
                            </div>

                            @if($appointment->provider)
                                <div class="mt-1 flex items-center gap-2 text-sm text-zinc-600 dark:text-zinc-400">
                                    @if($appointment->provider->specialty)
                                        <span>{{ $appointment->provider->specialty }}</span>
                                        <span>•</span>
                                    @endif
                                    @if($appointment->provider->system)
                                        <span>{{ $appointment->provider->system->name }}</span>
                                    @endif
                                </div>
                            @endif

                            <div class="mt-4 flex flex-wrap items-center gap-4 text-sm text-zinc-600 dark:text-zinc-400">
                                <span class="flex items-center gap-1.5">
                                    <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                    {{ $appointment->date->format('l, F j, Y') }}
                                </span>
                                @if($appointment->time)
                                    <span class="flex items-center gap-1.5">
                                        <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        {{ \Carbon\Carbon::parse($appointment->time)->format('g:i A') }}
                                    </span>
                                @endif
                                @if($appointment->location)
                                    <span class="flex items-center gap-1.5">
                                        <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        </svg>
                                        {{ $appointment->location }}
                                    </span>
                                @endif
                            </div>

                            @if($appointment->summary)
                                <div class="mt-4">
                                    <p class="text-sm text-zinc-700 dark:text-zinc-300">{{ $appointment->summary }}</p>
                                </div>
                            @endif

                            @if($appointment->patient_notes)
                                <div class="mt-3 rounded-lg bg-zinc-50 p-3 dark:bg-zinc-900/50">
                                    <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">My Notes</p>
                                    <p class="mt-1 text-sm text-zinc-700 dark:text-zinc-300">{{ $appointment->patient_notes }}</p>
                                </div>
                            @endif

                            @if($appointment->documents->count() > 0 || $appointment->tasks->count() > 0)
                                <div class="mt-4 flex gap-4 text-sm">
                                    @if($appointment->documents->count() > 0)
                                        <span class="text-zinc-600 dark:text-zinc-400">
                                            {{ $appointment->documents->count() }} {{ Str::plural('document', $appointment->documents->count()) }}
                                        </span>
                                    @endif
                                    @if($appointment->tasks->count() > 0)
                                        <span class="text-zinc-600 dark:text-zinc-400">
                                            {{ $appointment->tasks->count() }} {{ Str::plural('task', $appointment->tasks->count()) }}
                                        </span>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="flex flex-col items-center justify-center rounded-xl border border-zinc-200 bg-white py-12 dark:border-zinc-700 dark:bg-zinc-800">
                    <svg class="size-16 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    <p class="mt-4 text-lg font-medium text-zinc-900 dark:text-zinc-100">
                        @if($filter === 'upcoming')
                            No upcoming appointments
                        @elseif($filter === 'past')
                            No past appointments
                        @else
                            No appointments yet
                        @endif
                    </p>
                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                        @if($filter === 'upcoming')
                            Your upcoming appointments will appear here
                        @elseif($filter === 'past')
                            Your past appointments will appear here
                        @else
                            Get started by scheduling your first appointment
                        @endif
                    </p>
                </div>
            @endforelse
        </div>
</div>
