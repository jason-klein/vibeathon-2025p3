<?php

use App\Models\CommunityEvent;
use function Livewire\Volt\{state, mount};

state(['event' => null]);

mount(function (int $eventId) {
    $this->event = CommunityEvent::with('partner')->findOrFail($eventId);
});

?>

<div class="mx-auto max-w-4xl">
    <div class="mb-6">
        <a href="{{ route('dashboard') }}" class="inline-flex items-center text-sm font-medium text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100">
            <svg class="mr-2 size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
            Back to Dashboard
        </a>
    </div>

    <div class="rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
        {{-- Header --}}
        <div class="border-b border-zinc-200 p-6 dark:border-zinc-700">
            <div class="flex items-start gap-4">
                <div class="rounded-full bg-purple-100 p-3 dark:bg-purple-900/30">
                    <svg class="size-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <div class="flex-1">
                    <h1 class="text-2xl font-semibold text-zinc-900 dark:text-zinc-100">{{ $event->partner->name }}</h1>
                    @if($event->partner->is_nonprofit)
                        <span class="mt-2 inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-700 dark:bg-green-900/30 dark:text-green-400">
                            Nonprofit Organization
                        </span>
                    @endif
                    @if($event->partner->is_sponsor)
                        <span class="mt-2 inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">
                            Community Sponsor
                        </span>
                    @endif
                </div>
            </div>
        </div>

        {{-- Event Details --}}
        <div class="p-6">
            <div class="space-y-6">
                {{-- Date & Time --}}
                <div>
                    <h2 class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Date & Time</h2>
                    <div class="mt-2 flex flex-wrap items-center gap-4 text-zinc-900 dark:text-zinc-100">
                        <div class="flex items-center gap-2">
                            <svg class="size-5 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            <span class="font-medium">{{ $event->date->format('l, F j, Y') }}</span>
                        </div>
                        @if($event->time)
                            <div class="flex items-center gap-2">
                                <svg class="size-5 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <span class="font-medium">{{ \Carbon\Carbon::parse($event->time)->format('g:i A') }}</span>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Location --}}
                @if($event->location)
                    <div>
                        <h2 class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Location</h2>
                        <div class="mt-2 flex items-start gap-2 text-zinc-900 dark:text-zinc-100">
                            <svg class="size-5 shrink-0 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            <span class="font-medium">{{ $event->location }}</span>
                        </div>
                    </div>
                @endif

                {{-- Description --}}
                <div>
                    <h2 class="text-sm font-medium text-zinc-500 dark:text-zinc-400">About This Event</h2>
                    <div class="mt-2 text-zinc-700 dark:text-zinc-300">
                        <p class="whitespace-pre-line leading-relaxed">{{ $event->description }}</p>
                    </div>
                </div>

                {{-- Partner Provided Badge --}}
                @if($event->is_partner_provided)
                    <div class="rounded-lg border border-blue-200 bg-blue-50 p-4 dark:border-blue-800 dark:bg-blue-900/20">
                        <div class="flex items-start gap-3">
                            <svg class="size-5 shrink-0 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <div>
                                <p class="text-sm font-medium text-blue-900 dark:text-blue-100">Partner-Provided Event</p>
                                <p class="mt-1 text-sm text-blue-700 dark:text-blue-300">This event was submitted directly by the community partner.</p>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Footer Actions --}}
        <div class="border-t border-zinc-200 bg-zinc-50 px-6 py-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex flex-wrap items-center justify-between gap-4">
                @if($event->link)
                    <div class="flex flex-1 flex-wrap items-center gap-3">
                        <a href="{{ $event->link }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                            <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                            </svg>
                            View Event Details
                        </a>
                        <a href="{{ route('dashboard') }}" class="rounded-md border border-zinc-300 bg-white px-4 py-2 text-sm font-semibold text-zinc-900 shadow-sm hover:bg-zinc-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-zinc-900 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100 dark:hover:bg-zinc-700">
                            Back to Dashboard
                        </a>
                    </div>
                @else
                    <p class="text-sm text-zinc-600 dark:text-zinc-400">
                        Interested in attending? Contact {{ $event->partner->name }} for more information.
                    </p>
                    <a href="{{ route('dashboard') }}" class="rounded-md bg-zinc-900 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-zinc-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-zinc-900 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-100">
                        Back to Dashboard
                    </a>
                @endif
            </div>
        </div>
    </div>
</div>
