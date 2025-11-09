<?php

use App\Models\CommunityEvent;
use App\Support\Helpers\DistanceCalculator;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    #[\Livewire\Attributes\Url]
    public string $keyword = '';

    #[\Livewire\Attributes\Url]
    public ?string $startDate = null;

    #[\Livewire\Attributes\Url]
    public ?string $endDate = null;

    #[\Livewire\Attributes\Url]
    public ?int $maxDistance = null;

    public function mount(): void
    {
        // Set default start date to today if not provided
        if (! $this->startDate) {
            $this->startDate = today()->format('Y-m-d');
        }
    }

    public function clearFilters(): void
    {
        $this->reset(['keyword', 'startDate', 'endDate', 'maxDistance']);
        $this->startDate = today()->format('Y-m-d');
        $this->resetPage();
    }

    public function updatedKeyword(): void
    {
        $this->resetPage();
    }

    public function updatedStartDate(): void
    {
        $this->resetPage();
    }

    public function updatedEndDate(): void
    {
        $this->resetPage();
    }

    public function updatedMaxDistance(): void
    {
        $this->resetPage();
    }

    public function with(): array
    {
        $patient = Auth::user()->patient;

        $query = CommunityEvent::with('partner')
            ->orderBy('date')
            ->orderBy('time');

        // Apply keyword filter
        if ($this->keyword) {
            $keyword = $this->keyword;
            $query->where(function ($q) use ($keyword) {
                $q->whereHas('partner', fn ($q) => $q->where('name', 'like', "%{$keyword}%"))
                    ->orWhere('description', 'like', "%{$keyword}%")
                    ->orWhere('location', 'like', "%{$keyword}%");
            });
        }

        // Apply date range filters
        if ($this->startDate) {
            $query->where('date', '>=', $this->startDate);
        }

        if ($this->endDate) {
            $query->where('date', '<=', $this->endDate);
        }

        // Get events
        $events = $query->get();

        // Apply distance filtering if needed
        if ($this->maxDistance && $patient?->latitude && $patient?->longitude) {
            $events = $events->filter(function ($event) use ($patient) {
                if (! $event->latitude || ! $event->longitude) {
                    return true; // Include events without coordinates
                }

                $distance = DistanceCalculator::calculate(
                    $patient->latitude,
                    $patient->longitude,
                    $event->latitude,
                    $event->longitude
                );

                return $distance <= $this->maxDistance;
            });
        }

        // Manual pagination
        $perPage = 15;
        $currentPage = $this->getPage();
        $total = $events->count();
        $items = $events->slice(($currentPage - 1) * $perPage, $perPage)->values();

        $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $currentPage,
            ['path' => request()->url()]
        );

        return [
            'events' => $paginator,
            'patient' => $patient,
            'totalCount' => $total,
        ];
    }

    public function calculateDistance($event): ?float
    {
        $patient = Auth::user()->patient;

        if (! $patient || ! $event->latitude || ! $event->longitude || ! $patient->latitude || ! $patient->longitude) {
            return null;
        }

        return DistanceCalculator::calculate(
            $patient->latitude,
            $patient->longitude,
            $event->latitude,
            $event->longitude
        );
    }

    public function formatDistance(?float $distance): ?string
    {
        if ($distance === null) {
            return null;
        }

        return DistanceCalculator::format($distance);
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-6">
    {{-- Page Header --}}
    <div>
        <h1 class="text-2xl font-semibold text-zinc-900 dark:text-zinc-100">Community Events</h1>
        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
            Browse upcoming health and wellness events in your area
            @if($totalCount > 0)
                <span class="font-medium">({{ $totalCount }} {{ Str::plural('event', $totalCount) }})</span>
            @endif
        </p>
    </div>

    {{-- Filter Section --}}
    <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
        <div class="space-y-4">
            {{-- Keyword Search --}}
            <div>
                <flux:input
                    wire:model.live.debounce.300ms="keyword"
                    placeholder="Search events by keyword, partner, or location..."
                    icon="magnifying-glass"
                />
            </div>

            {{-- Date Filters --}}
            <div class="grid gap-4 md:grid-cols-2">
                <flux:input
                    type="date"
                    wire:model.live="startDate"
                    label="Start Date"
                />
                <flux:input
                    type="date"
                    wire:model.live="endDate"
                    label="End Date"
                />
            </div>

            {{-- Distance Filter --}}
            @if($patient?->latitude && $patient?->longitude)
                <div>
                    <flux:select
                        wire:model.live="maxDistance"
                        label="Distance from your location"
                    >
                        <option value="">Any distance</option>
                        <option value="5">Within 5 miles</option>
                        <option value="10">Within 10 miles</option>
                        <option value="25">Within 25 miles</option>
                        <option value="50">Within 50 miles</option>
                        <option value="100">Within 100 miles</option>
                    </flux:select>
                </div>
            @endif

            {{-- Filter Actions --}}
            <div class="flex items-center gap-3">
                <flux:button wire:click="clearFilters" variant="ghost" size="sm">
                    <svg class="mr-2 size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                    Clear Filters
                </flux:button>

                {{-- Active Filters Indicator --}}
                @if($keyword || $endDate || $maxDistance)
                    <span class="text-sm text-zinc-600 dark:text-zinc-400">
                        Filters active
                    </span>
                @endif
            </div>
        </div>

        {{-- Loading Overlay --}}
        <div wire:loading wire:target="keyword,startDate,endDate,maxDistance" class="absolute inset-0 z-10 flex items-center justify-center rounded-xl bg-white/70 dark:bg-zinc-800/70">
            <div class="flex items-center gap-2 rounded-lg bg-white px-4 py-2 shadow-lg dark:bg-zinc-800">
                <svg class="size-5 animate-spin text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                <span class="text-sm font-medium text-zinc-900 dark:text-zinc-100">Loading events...</span>
            </div>
        </div>
    </div>

    {{-- Event Cards --}}
    <div>
        @if($events->count() > 0)
            <div class="space-y-4">
                @foreach($events as $event)
                    <a
                        href="{{ route('events.show', $event->id) }}"
                        wire:key="event-{{ $event->id }}"
                        class="block rounded-lg border-2 border-purple-200 bg-purple-50 p-4 transition hover:border-purple-300 hover:shadow-sm dark:border-purple-800 dark:bg-purple-900/20 dark:hover:border-purple-700"
                        wire:navigate
                    >
                        <div class="flex items-start gap-3">
                            {{-- Icon --}}
                            <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-purple-100 dark:bg-purple-900/40">
                                <svg class="size-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                </svg>
                            </div>

                            {{-- Content --}}
                            <div class="flex-1">
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex items-center rounded-full bg-purple-100 px-2 py-0.5 text-xs font-semibold text-purple-700 dark:bg-purple-900/40 dark:text-purple-400">
                                        Community Event
                                    </span>
                                    @if($event->partner->is_nonprofit)
                                        <span class="inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700 dark:bg-green-900/30 dark:text-green-400">
                                            Nonprofit
                                        </span>
                                    @endif
                                </div>

                                <p class="mt-1 font-medium text-zinc-900 dark:text-zinc-100">{{ $event->partner->name }}</p>

                                @if($event->description)
                                    <p class="mt-1 text-sm text-zinc-700 dark:text-zinc-300">
                                        {{ Str::limit($event->description, 150) }}
                                    </p>
                                @endif

                                {{-- Metadata --}}
                                <div class="mt-2 flex flex-wrap items-center gap-4 text-sm text-zinc-600 dark:text-zinc-400">
                                    <span class="flex items-center gap-1">
                                        <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        </svg>
                                        {{ $event->date->format('M j, Y') }}
                                    </span>

                                    @if($event->time)
                                        <span class="flex items-center gap-1">
                                            <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            {{ \Carbon\Carbon::parse($event->time)->format('g:i A') }}
                                        </span>
                                    @endif

                                    @if($event->location)
                                        @php
                                            $distance = $this->calculateDistance($event);
                                        @endphp
                                        <span class="flex items-center gap-1">
                                            <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            </svg>
                                            {{ $event->location }}@if($distance !== null) ({{ $this->formatDistance($distance) }})@endif
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>

            {{-- Pagination --}}
            <div class="mt-6">
                {{ $events->links() }}
            </div>
        @else
            {{-- Empty State --}}
            <div class="rounded-xl border border-zinc-200 bg-white py-12 text-center dark:border-zinc-700 dark:bg-zinc-800">
                <svg class="mx-auto size-12 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
                <p class="mt-4 text-sm font-medium text-zinc-900 dark:text-zinc-100">No events found</p>
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                    @if($keyword || $endDate || $maxDistance)
                        Try adjusting your filters to see more results
                    @else
                        Check back soon for upcoming community events
                    @endif
                </p>
                @if($keyword || $endDate || $maxDistance)
                    <flux:button wire:click="clearFilters" variant="primary" size="sm" class="mt-4">
                        Clear Filters
                    </flux:button>
                @endif
            </div>
        @endif
    </div>
</div>
