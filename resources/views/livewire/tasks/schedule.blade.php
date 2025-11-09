<?php

use App\Models\HealthcareProvider;
use App\Models\Patient;
use App\Models\PatientTask;
use App\Support\Helpers\DistanceCalculator;
use Illuminate\Support\Facades\Auth;

use function Livewire\Volt\computed;
use function Livewire\Volt\layout;
use function Livewire\Volt\mount;
use function Livewire\Volt\state;
use function Livewire\Volt\title;

layout('components.layouts.app');
title('Schedule Appointment');

state(['taskId' => null]);
state(['selectedProviderId' => null]);
state(['isScheduling' => false]);
state(['schedulingStep' => 'scheduling']); // 'scheduling' or 'success'
state(['confirmedAppointment' => null]);

mount(function ($taskId) {
    $this->taskId = $taskId;

    // Verify task belongs to user
    $task = PatientTask::find($taskId);
    $patient = Patient::where('user_id', Auth::id())->first();

    if (! $task || ! $patient || $task->patient_id !== $patient->id) {
        abort(404);
    }

    if (! $task->is_scheduling_task) {
        abort(404, 'This is not a scheduling task');
    }
});

$task = computed(function () {
    return PatientTask::with(['appointment', 'scheduledAppointment'])
        ->find($this->taskId);
});

$patient = computed(function () {
    return Patient::where('user_id', Auth::id())->first();
});

$preferredProviders = computed(function () {
    $task = $this->task;
    $patient = $this->patient;

    $query = HealthcareProvider::with('system')
        ->whereHas('system', function ($q) {
            $q->where('is_preferred', true);
        });

    if ($task->provider_specialty_needed) {
        $query->where('specialty', $task->provider_specialty_needed);
    }

    $providers = $query->get();

    // Calculate distances
    return $providers->map(function ($provider) use ($patient) {
        $provider->distance = DistanceCalculator::calculate(
            $patient->latitude,
            $patient->longitude,
            $provider->latitude,
            $provider->longitude
        );

        return $provider;
    })->sortBy('distance');
});

$otherProviders = computed(function () {
    $task = $this->task;
    $patient = $this->patient;

    $query = HealthcareProvider::with('system')
        ->whereHas('system', function ($q) {
            $q->where('is_preferred', false);
        });

    if ($task->provider_specialty_needed) {
        $query->where('specialty', $task->provider_specialty_needed);
    }

    $providers = $query->get();

    // Calculate distances
    return $providers->map(function ($provider) use ($patient) {
        $provider->distance = DistanceCalculator::calculate(
            $patient->latitude,
            $patient->longitude,
            $provider->latitude,
            $provider->longitude
        );

        return $provider;
    })->sortBy('distance');
});

$availability = computed(function () {
    // Generate mock availability slots for the next 2 weeks (weekdays only)
    // Returns same slots for consistency within a request
    $slots = collect();
    $startDate = now()->addDay();
    $endDate = now()->addWeeks(2);

    $currentDate = $startDate->copy();

    while ($currentDate->lte($endDate) && $slots->count() < 5) {
        // Skip weekends
        if ($currentDate->isWeekday()) {
            // Random time between 8 AM and 4 PM
            $hour = rand(8, 15);
            $minute = [0, 30][rand(0, 1)];

            $slots->push([
                'date' => $currentDate->format('Y-m-d'),
                'time' => sprintf('%02d:%02d:00', $hour, $minute),
                'display' => $currentDate->format('M j').' '.sprintf('%d:%02d %s',
                    $hour > 12 ? $hour - 12 : $hour,
                    $minute,
                    $hour >= 12 ? 'PM' : 'AM'
                ),
            ]);
        }

        $currentDate->addDay();
    }

    return $slots;
});

$selectProvider = function ($providerId) {
    $this->selectedProviderId = $providerId;
};

$bookAppointment = function ($providerId, $date, $time) {
    $provider = HealthcareProvider::findOrFail($providerId);
    $task = $this->task;
    $patient = $this->patient;

    // Show scheduling modal
    $this->isScheduling = true;
    $this->schedulingStep = 'scheduling';

    // Create the appointment immediately (visual delay handled in frontend)
    $appointment = $patient->appointments()->create([
        'healthcare_provider_id' => $provider->id,
        'date' => $date,
        'time' => $time,
        'location' => $provider->location,
        'summary' => $task->description,
        'scheduled_from_task_id' => $task->id,
    ]);

    // Mark task as complete
    $task->update([
        'completed_at' => now(),
    ]);

    // Store appointment details for modal display
    $this->confirmedAppointment = [
        'confirmation_number' => $appointment->confirmation_number,
        'date' => \Carbon\Carbon::parse($date)->format('M j, Y'),
        'time' => \Carbon\Carbon::parse($time)->format('g:i A'),
        'provider_name' => $provider->name,
        'location' => $provider->location,
    ];
};

$closeModal = function () {
    $this->isScheduling = false;
    $this->redirect(route('appointments.index'));
};

?>

<div
    class="flex h-full w-full flex-1 flex-col gap-6"
    x-data="{
        schedulingDelay: null,
        init() {
            // Watch for when scheduling starts
            this.$watch('$wire.isScheduling', (value) => {
                if (value && $wire.schedulingStep === 'scheduling') {
                    // Random delay between 3-5 seconds (3000-5000ms)
                    const delay = Math.floor(Math.random() * 2000) + 3000;
                    this.schedulingDelay = setTimeout(() => {
                        $wire.schedulingStep = 'success';
                    }, delay);
                }
            });
        }
    }"
>
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-zinc-900 dark:text-zinc-100">Schedule Appointment</h1>
            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ $this->task->description }}</p>
        </div>
        <a href="{{ route('tasks.index') }}" class="text-sm font-medium text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100">
            ← Back to Tasks
        </a>
    </div>

    @if($this->task->provider_specialty_needed)
        <div class="rounded-lg bg-blue-50 p-4 dark:bg-blue-950">
            <div class="flex items-center gap-2">
                <svg class="size-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <p class="text-sm font-medium text-blue-900 dark:text-blue-100">
                    Looking for: <span class="font-semibold">{{ $this->task->provider_specialty_needed }}</span>
                </p>
            </div>
        </div>
    @endif

    {{-- Preferred System Providers --}}
    @if($this->preferredProviders->count() > 0)
        <div>
            <h2 class="mb-4 text-lg font-semibold text-zinc-900 dark:text-zinc-100">Preferred Healthcare System Providers</h2>
            <div class="space-y-4">
                @foreach($this->preferredProviders as $provider)
                    <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
                        {{-- Provider Info --}}
                        <div class="p-6">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2">
                                        <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ $provider->name }}</h3>
                                        <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-700 dark:bg-green-900/30 dark:text-green-400">
                                            Preferred System
                                        </span>
                                    </div>

                                    <div class="mt-2 space-y-1 text-sm text-zinc-600 dark:text-zinc-400">
                                        @if($provider->specialty)
                                            <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ $provider->specialty }}</p>
                                        @endif

                                        <div class="flex flex-wrap items-center gap-4">
                                            <span class="flex items-center gap-1">
                                                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                                </svg>
                                                {{ $provider->system->name }}
                                            </span>

                                            <span class="flex items-center gap-1">
                                                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                </svg>
                                                {{ $provider->location }}
                                            </span>

                                            @if($provider->distance !== null)
                                                <span class="flex items-center gap-1">
                                                    <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                                                    </svg>
                                                    {{ DistanceCalculator::format($provider->distance) }}
                                                </span>
                                            @endif
                                        </div>

                                        @if($provider->phone)
                                            <p class="flex items-center gap-1">
                                                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                                </svg>
                                                {{ $provider->phone }}
                                            </p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Available Times --}}
                        <div class="border-t border-zinc-200 bg-zinc-50 p-6 dark:border-zinc-700 dark:bg-zinc-800">
                            <p class="mb-3 text-sm font-medium text-zinc-900 dark:text-zinc-100">Available Times:</p>
                            <div class="flex flex-wrap gap-2">
                                @foreach($this->availability as $slot)
                                    <button
                                        wire:click="bookAppointment({{ $provider->id }}, '{{ $slot['date'] }}', '{{ $slot['time'] }}')"
                                        type="button"
                                        class="inline-flex items-center rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600"
                                    >
                                        <svg class="mr-1.5 size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        </svg>
                                        {{ $slot['display'] }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Independent Providers --}}
    @if($this->otherProviders->count() > 0)
        <div>
            <h2 class="mb-2 text-lg font-semibold text-zinc-900 dark:text-zinc-100">Independent Providers</h2>
            <p class="mb-4 text-sm text-zinc-600 dark:text-zinc-400">
                These providers require manual scheduling outside of this app. Please contact them directly to schedule an appointment.
            </p>
            <div class="space-y-4">
                @foreach($this->otherProviders as $provider)
                    <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ $provider->name }}</h3>

                                <div class="mt-2 space-y-1 text-sm text-zinc-600 dark:text-zinc-400">
                                    @if($provider->specialty)
                                        <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ $provider->specialty }}</p>
                                    @endif

                                    <div class="flex flex-wrap items-center gap-4">
                                        <span class="flex items-center gap-1">
                                            <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                            </svg>
                                            {{ $provider->system->name }}
                                        </span>

                                        <span class="flex items-center gap-1">
                                            <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            </svg>
                                            {{ $provider->location }}
                                        </span>

                                        @if($provider->distance !== null)
                                            <span class="flex items-center gap-1">
                                                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                                                </svg>
                                                {{ DistanceCalculator::format($provider->distance) }}
                                            </span>
                                        @endif
                                    </div>

                                    @if($provider->phone)
                                        <p class="flex items-center gap-1">
                                            <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                            </svg>
                                            <a href="tel:{{ $provider->phone }}" class="text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300">{{ $provider->phone }}</a>
                                        </p>
                                    @endif

                                    @if($provider->email)
                                        <p class="flex items-center gap-1">
                                            <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                            </svg>
                                            <a href="mailto:{{ $provider->email }}" class="text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300">{{ $provider->email }}</a>
                                        </p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- No providers found --}}
    @if($this->preferredProviders->count() === 0 && $this->otherProviders->count() === 0)
        <div class="flex flex-col items-center justify-center rounded-xl border border-zinc-200 bg-white py-12 dark:border-zinc-700 dark:bg-zinc-800">
            <svg class="size-16 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <p class="mt-4 text-lg font-medium text-zinc-900 dark:text-zinc-100">No providers found</p>
            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                @if($this->task->provider_specialty_needed)
                    No providers available for {{ $this->task->provider_specialty_needed }}
                @else
                    No providers available at this time
                @endif
            </p>
        </div>
    @endif

    {{-- Scheduling Confirmation Modal --}}
    <flux:modal wire:model="isScheduling" class="min-w-[400px]">
        @if($schedulingStep === 'scheduling')
            {{-- Scheduling State --}}
            <div class="flex flex-col items-center justify-center p-8">
                <div class="mb-4">
                    <svg class="size-16 animate-spin text-blue-600" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Scheduling your appointment...</h3>
                <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">Please wait a moment</p>
            </div>
        @else
            {{-- Success State --}}
            <div class="flex flex-col items-center justify-center p-8">
                <div class="mb-4">
                    <div class="flex size-16 items-center justify-center rounded-full bg-green-100 dark:bg-green-900/30">
                        <svg class="size-10 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                </div>
                <h3 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">Appointment Scheduled!</h3>

                @if($confirmedAppointment)
                    <div class="mt-6 w-full space-y-3 rounded-lg bg-zinc-50 p-4 dark:bg-zinc-800">
                        <div class="flex items-center justify-between border-b border-zinc-200 pb-3 dark:border-zinc-700">
                            <span class="text-sm font-medium text-zinc-600 dark:text-zinc-400">Confirmation Number</span>
                            <span class="text-lg font-bold text-blue-600 dark:text-blue-400">{{ $confirmedAppointment['confirmation_number'] }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-zinc-600 dark:text-zinc-400">Date</span>
                            <span class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $confirmedAppointment['date'] }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-zinc-600 dark:text-zinc-400">Time</span>
                            <span class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $confirmedAppointment['time'] }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-zinc-600 dark:text-zinc-400">Provider</span>
                            <span class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $confirmedAppointment['provider_name'] }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-zinc-600 dark:text-zinc-400">Location</span>
                            <span class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $confirmedAppointment['location'] }}</span>
                        </div>
                    </div>
                @endif

                <div class="mt-6 w-full">
                    <flux:button wire:click="closeModal" variant="primary" class="w-full">
                        View Appointments
                    </flux:button>
                </div>
            </div>
        @endif
    </flux:modal>
</div>
