<?php

use App\Models\CommunityEvent;
use App\Models\Patient;
use App\Models\PatientTask;
use App\Support\Helpers\DistanceCalculator;
use Illuminate\Support\Facades\Auth;

use function Livewire\Volt\computed;
use function Livewire\Volt\state;

state(['patient' => function () {
    $user = Auth::user();
    $patient = Patient::where('user_id', $user->id)->first();

    if (! $patient) {
        return null;
    }

    return Patient::with([
        'appointments' => fn ($q) => $q->where('date', '>=', today())->orderBy('date')->orderBy('time')->limit(3)->with('provider.system'),
    ])->find($patient->id);
}]);

state(['upcomingAppointmentsCount' => fn () => Auth::user()->patient?->appointments()->where('date', '>=', today())->count() ?? 0]);
state(['activeTasksCount' => fn () => Auth::user()->patient?->tasks()->whereNull('completed_at')->count() ?? 0]);

state([
    'showSchedulingTaskConfirmation' => false,
    'taskToComplete' => null,
]);

$activeTasks = computed(function () {
    if (!$this->patient) {
        return collect();
    }

    return $this->patient->tasks()
        ->whereNull('completed_at')
        ->orderBy('created_at', 'desc')
        ->limit(5)
        ->with('scheduledAppointment')
        ->get();
});

$toggleTask = function (int $taskId) {
    $task = PatientTask::with('scheduledAppointment')->findOrFail($taskId);
    $this->authorize('update', $task);

    if ($task->patient_id !== Auth::user()->patient->id) {
        abort(403);
    }

    // If marking complete and it's a scheduling task without a scheduled appointment, show confirmation
    if (!$task->completed_at && $task->is_scheduling_task && !$task->scheduledAppointment) {
        $this->taskToComplete = $taskId;
        $this->showSchedulingTaskConfirmation = true;
        return;
    }

    $task->update([
        'completed_at' => $task->completed_at ? null : now(),
    ]);
};

$confirmTaskComplete = function () {
    if (!$this->taskToComplete) {
        return;
    }

    $task = PatientTask::findOrFail($this->taskToComplete);
    $this->authorize('update', $task);

    if ($task->patient_id !== Auth::user()->patient->id) {
        abort(403);
    }

    $task->update([
        'completed_at' => now(),
    ]);

    $this->showSchedulingTaskConfirmation = false;
    $this->taskToComplete = null;
};

$cancelTaskComplete = function () {
    $this->showSchedulingTaskConfirmation = false;
    $this->taskToComplete = null;
};

$personalizedFeed = computed(function () {
    $patient = $this->patient;
    $feed = collect();

    // Get upcoming appointments
    if ($patient) {
        $upcomingAppointments = $patient->appointments()
            ->with('provider.system')
            ->where('date', '>=', today())
            ->orderBy('date')
            ->orderBy('time')
            ->limit(10)
            ->get();

        foreach ($upcomingAppointments as $appointment) {
            $feed->push([
                'type' => 'appointment',
                'id' => $appointment->id,
                'date' => $appointment->date,
                'time' => $appointment->time,
                'title' => $appointment->provider?->name ?? 'No provider assigned',
                'subtitle' => $appointment->provider?->specialty,
                'location' => $appointment->location,
                'model' => $appointment,
            ]);
        }
    }

    // Get all future community events
    $events = CommunityEvent::with('partner')
        ->where('date', '>=', today())
        ->orderBy('date')
        ->orderBy('time')
        ->limit(10)
        ->get();

    // Basic filtering based on appointment summaries and task descriptions
    if ($patient) {
        $keywords = collect();

        // Extract keywords from past appointments (encounters)
        $pastAppointments = $patient->appointments()
            ->where('date', '<', today())
            ->get();

        foreach ($pastAppointments as $appointment) {
            if ($appointment->summary) {
                $keywords = $keywords->merge(str_word_count(strtolower($appointment->summary), 1));
            }
        }

        // Extract keywords from tasks
        $tasks = $patient->tasks()->get();
        foreach ($tasks as $task) {
            if ($task->description) {
                $keywords = $keywords->merge(str_word_count(strtolower($task->description), 1));
            }
        }

        // Filter out common words
        $commonWords = ['the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'with', 'from', 'by', 'of', 'is', 'are', 'was', 'were', 'be', 'been', 'being', 'have', 'has', 'had', 'do', 'does', 'did', 'will', 'would', 'should', 'could', 'may', 'might', 'must', 'can'];
        $keywords = $keywords->filter(fn ($word) => strlen($word) > 3 && ! in_array($word, $commonWords))->unique();

        // Filter events based on keywords
        if ($keywords->isNotEmpty()) {
            $filteredEvents = $events->filter(function ($event) use ($keywords) {
                $eventText = strtolower($event->description.' '.$event->partner->name);
                foreach ($keywords as $keyword) {
                    if (str_contains($eventText, $keyword)) {
                        return true;
                    }
                }

                return false;
            });

            // If we found matching events, use them; otherwise show all events
            if ($filteredEvents->isNotEmpty()) {
                $events = $filteredEvents;
            }
        }
    }

    foreach ($events->take(10) as $event) {
        $feed->push([
            'type' => 'event',
            'id' => $event->id,
            'date' => $event->date,
            'time' => $event->time,
            'title' => $event->partner->name,
            'subtitle' => Str::limit($event->description, 120),
            'location' => $event->location,
            'model' => $event,
        ]);
    }

    // Sort by date and time
    return $feed->sortBy(function ($item) {
        $dateTime = $item['date']->format('Y-m-d');
        if ($item['time']) {
            $dateTime .= ' '.$item['time']->format('H:i');
        }

        return $dateTime;
    })->take(10)->values();
});

$calculateDistance = function ($appointment) {
    $patient = $this->patient;
    if (! $patient || ! $appointment->provider) {
        return null;
    }

    return DistanceCalculator::calculate(
        $patient->latitude,
        $patient->longitude,
        $appointment->provider->latitude,
        $appointment->provider->longitude
    );
};

$formatDistance = fn ($distance) => DistanceCalculator::format($distance);

?>
<div class="flex h-full w-full flex-1 flex-col gap-6">
        {{-- Welcome Header --}}
        <div>
            <h1 class="text-2xl font-semibold text-zinc-900 dark:text-zinc-100">Welcome back, {{ Auth::user()->name }}</h1>
            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Here's what's happening with your healthcare journey</p>
        </div>

        {{-- Stats Cards --}}
        <div class="grid gap-4 md:grid-cols-3">
            <a href="#upcoming-appointments" class="rounded-xl border border-zinc-200 bg-white p-6 transition-all hover:border-green-300 hover:shadow-md dark:border-zinc-700 dark:bg-zinc-800 dark:hover:border-green-700">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-zinc-600 dark:text-zinc-400">Upcoming Appointments</p>
                        <p class="mt-2 text-3xl font-semibold text-zinc-900 dark:text-zinc-100">{{ $upcomingAppointmentsCount }}</p>
                    </div>
                    <div class="rounded-full bg-green-100 p-3 dark:bg-green-900/30">
                        <svg class="size-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                </div>
            </a>

            <a href="#active-tasks" class="rounded-xl border border-zinc-200 bg-white p-6 transition-all hover:border-blue-300 hover:shadow-md dark:border-zinc-700 dark:bg-zinc-800 dark:hover:border-blue-700">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-zinc-600 dark:text-zinc-400">Active Tasks</p>
                        <p class="mt-2 text-3xl font-semibold text-zinc-900 dark:text-zinc-100">{{ $activeTasksCount }}</p>
                    </div>
                    <div class="rounded-full bg-blue-100 p-3 dark:bg-blue-900/30">
                        <svg class="size-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                        </svg>
                    </div>
                </div>
            </a>

            <a href="#health-journey" class="rounded-xl border border-zinc-200 bg-white p-6 transition-all hover:border-purple-300 hover:shadow-md dark:border-zinc-700 dark:bg-zinc-800 dark:hover:border-purple-700">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-zinc-600 dark:text-zinc-400">Health Journey</p>
                        <p class="mt-2 text-3xl font-semibold text-zinc-900 dark:text-zinc-100">On Track</p>
                    </div>
                    <div class="rounded-full bg-purple-100 p-3 dark:bg-purple-900/30">
                        <svg class="size-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </a>
        </div>

        {{-- Main Content Grid --}}
        <div class="grid flex-1 gap-6 lg:grid-cols-2">
            {{-- Upcoming Appointments --}}
            <div id="upcoming-appointments" class="flex flex-col rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800 scroll-mt-6">
                <div class="border-b border-zinc-200 p-6 dark:border-zinc-700">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Upcoming Appointments</h2>
                        <a href="{{ route('appointments.index') }}" class="text-sm font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300">View all</a>
                    </div>
                </div>
                <div class="flex-1 p-6">
                    @if($patient && $patient->appointments->count() > 0)
                        <div class="space-y-4">
                            @foreach($patient->appointments as $appointment)
                                <a href="{{ route('appointments.show', $appointment->id) }}" class="block rounded-lg border border-zinc-200 p-4 transition hover:border-zinc-300 hover:shadow-sm dark:border-zinc-700 dark:hover:border-zinc-600">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1">
                                            <div class="flex items-center gap-2">
                                                <p class="font-medium text-zinc-900 dark:text-zinc-100">
                                                    {{ $appointment->provider?->name ?? 'No provider assigned' }}
                                                </p>
                                                @if($appointment->confirmation_number)
                                                    <span class="inline-flex items-center rounded-md bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">
                                                        {{ $appointment->confirmation_number }}
                                                    </span>
                                                @endif
                                            </div>
                                            @if($appointment->provider?->specialty)
                                                <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ $appointment->provider->specialty }}</p>
                                            @endif
                                            <div class="mt-2 flex flex-wrap items-center gap-4 text-sm text-zinc-600 dark:text-zinc-400">
                                                <span class="flex items-center gap-1">
                                                    <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                    </svg>
                                                    {{ $appointment->date->format('M j, Y') }}
                                                </span>
                                                @if($appointment->time)
                                                    <span class="flex items-center gap-1">
                                                        <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                        </svg>
                                                        {{ \Carbon\Carbon::parse($appointment->time)->format('g:i A') }}
                                                    </span>
                                                @endif
                                                @php
                                                    $distance = $this->calculateDistance($appointment);
                                                @endphp
                                                @if($distance !== null)
                                                    <span class="flex items-center gap-1">
                                                        <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                        </svg>
                                                        {{ $this->formatDistance($distance) }}
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    @else
                        <div class="flex h-full items-center justify-center py-12 text-center">
                            <div>
                                <svg class="mx-auto size-12 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                                <p class="mt-4 text-sm font-medium text-zinc-900 dark:text-zinc-100">No upcoming appointments</p>
                                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Schedule your first appointment to get started</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Active Tasks --}}
            <div id="active-tasks" class="flex flex-col rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800 scroll-mt-6">
                <div class="border-b border-zinc-200 p-6 dark:border-zinc-700">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Active Tasks</h2>
                        <a href="{{ route('tasks.index') }}" class="text-sm font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300">View all</a>
                    </div>
                </div>
                <div class="flex-1 p-6">
                    @if($patient && $this->activeTasks->count() > 0)
                        <div class="space-y-3">
                            @foreach($this->activeTasks as $task)
                                <div
                                    wire:key="task-{{ $task->id }}"
                                    onclick="window.location.href='{{ route('tasks.show', $task->id) }}'"
                                    class="flex cursor-pointer items-start gap-3 rounded-lg border border-zinc-200 p-4 transition hover:border-zinc-300 hover:shadow-sm dark:border-zinc-700 dark:hover:border-zinc-600"
                                >
                                    <button
                                        wire:click.stop="toggleTask({{ $task->id }})"
                                        wire:loading.attr="disabled"
                                        wire:target="toggleTask({{ $task->id }})"
                                        class="flex size-5 shrink-0 items-center justify-center rounded border-2 transition-colors {{ $task->completed_at ? 'border-green-500 bg-green-500' : 'border-zinc-300 bg-white hover:border-green-600 dark:border-zinc-600 dark:bg-zinc-800 dark:hover:border-green-600' }}"
                                    >
                                        <span wire:loading.remove wire:target="toggleTask({{ $task->id }})">
                                            @if($task->completed_at)
                                                <svg class="size-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                                                </svg>
                                            @endif
                                        </span>
                                        <svg wire:loading wire:target="toggleTask({{ $task->id }})" class="size-3 animate-spin text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                        </svg>
                                    </button>
                                    <div class="flex-1">
                                        <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ $task->description }}</p>
                                        @if($task->instructions)
                                            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ Str::limit($task->instructions, 100) }}</p>
                                        @endif
                                        <div class="mt-2 flex flex-wrap items-center gap-2">
                                            @if($task->is_scheduling_task)
                                                <span class="inline-flex items-center rounded-full bg-blue-100 px-2 py-1 text-xs font-medium text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">
                                                    Scheduling Task
                                                </span>
                                                @if(!$task->scheduledAppointment)
                                                    <span onclick="event.preventDefault(); event.stopPropagation(); window.location.href='{{ route('tasks.schedule', $task->id) }}';" class="inline-flex items-center rounded-md bg-green-600 px-3 py-1 text-xs font-semibold text-white shadow-sm hover:bg-green-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-600 cursor-pointer">
                                                        <svg class="-ml-0.5 mr-1.5 size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                        </svg>
                                                        Schedule
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-700 dark:bg-green-900/30 dark:text-green-400">
                                                        <svg class="mr-1 size-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                        </svg>
                                                        Appointment Scheduled
                                                    </span>
                                                @endif
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="flex h-full items-center justify-center py-12 text-center">
                            <div>
                                <svg class="mx-auto size-12 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                </svg>
                                <p class="mt-4 text-sm font-medium text-zinc-900 dark:text-zinc-100">No active tasks</p>
                                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">You're all caught up!</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Executive Summary Card --}}
        @if($patient && $patient->executive_summary)
            <a id="health-journey" href="/timeline" class="block scroll-mt-6 rounded-xl border-2 border-purple-200 bg-gradient-to-br from-purple-50 to-white p-6 shadow-sm transition hover:border-purple-300 hover:shadow-md dark:border-purple-800 dark:from-purple-900/20 dark:to-zinc-800">
                <div class="flex items-start gap-4">
                    <div class="rounded-full bg-purple-100 p-3 dark:bg-purple-900/30">
                        <svg class="size-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Your Health Story in Plain English</h2>
                        <div class="mt-2 text-sm leading-relaxed text-zinc-700 dark:text-zinc-300">{!! nl2br(e($patient->executive_summary)) !!}</div>
                        @if($patient->executive_summary_updated_at)
                            <p class="mt-3 text-xs text-zinc-500 dark:text-zinc-400">Last updated {{ $patient->executive_summary_updated_at->diffForHumans() }}</p>
                        @endif
                        <div class="mt-3 flex items-center text-sm font-medium text-purple-600 dark:text-purple-400">
                            View full timeline
                            <svg class="ml-1 size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </div>
                    </div>
                </div>
            </a>
        @endif

        {{-- Divider --}}
        <div class="border-t border-zinc-200 dark:border-zinc-700"></div>

        {{-- Personalized Feed --}}
        <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
            <div class="border-b border-zinc-200 p-6 dark:border-zinc-700">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Personalized Feed</h2>
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Your upcoming appointments and recommended community events</p>
            </div>
            <div class="p-6">
                @if($this->personalizedFeed->count() > 0)
                    <div class="space-y-4">
                        @foreach($this->personalizedFeed as $item)
                            @if($item['type'] === 'appointment')
                                {{-- Appointment Item --}}
                                <a href="{{ route('appointments.show', $item['id']) }}" class="block rounded-lg border-2 border-green-200 bg-green-50 p-4 transition hover:border-green-300 hover:shadow-sm dark:border-green-800 dark:bg-green-900/20 dark:hover:border-green-700">
                                    <div class="flex items-start gap-3">
                                        <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-green-100 dark:bg-green-900/40">
                                            <svg class="size-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                            </svg>
                                        </div>
                                        <div class="flex-1">
                                            <div class="flex items-center gap-2">
                                                <span class="inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-xs font-semibold text-green-700 dark:bg-green-900/40 dark:text-green-400">
                                                    Appointment
                                                </span>
                                            </div>
                                            <p class="mt-1 font-medium text-zinc-900 dark:text-zinc-100">{{ $item['title'] }}</p>
                                            @if($item['subtitle'])
                                                <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ $item['subtitle'] }}</p>
                                            @endif
                                            <div class="mt-2 flex flex-wrap items-center gap-4 text-sm text-zinc-600 dark:text-zinc-400">
                                                <span class="flex items-center gap-1">
                                                    <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                    </svg>
                                                    {{ $item['date']->format('M j, Y') }}
                                                </span>
                                                @if($item['time'])
                                                    <span class="flex items-center gap-1">
                                                        <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                        </svg>
                                                        {{ \Carbon\Carbon::parse($item['time'])->format('g:i A') }}
                                                    </span>
                                                @endif
                                                @php
                                                    $distance = $this->calculateDistance($item['model']);
                                                @endphp
                                                @if($distance !== null)
                                                    <span class="flex items-center gap-1">
                                                        <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                        </svg>
                                                        {{ $this->formatDistance($distance) }}
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            @else
                                {{-- Community Event Item --}}
                                <a href="{{ route('events.show', $item['id']) }}" class="block rounded-lg border-2 border-purple-200 bg-purple-50 p-4 transition hover:border-purple-300 hover:shadow-sm dark:border-purple-800 dark:bg-purple-900/20 dark:hover:border-purple-700">
                                    <div class="flex items-start gap-3">
                                        <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-purple-100 dark:bg-purple-900/40">
                                            <svg class="size-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                            </svg>
                                        </div>
                                        <div class="flex-1">
                                            <div class="flex items-center gap-2">
                                                <span class="inline-flex items-center rounded-full bg-purple-100 px-2 py-0.5 text-xs font-semibold text-purple-700 dark:bg-purple-900/40 dark:text-purple-400">
                                                    Community Event
                                                </span>
                                            </div>
                                            <p class="mt-1 font-medium text-zinc-900 dark:text-zinc-100">{{ $item['title'] }}</p>
                                            @if($item['subtitle'])
                                                <p class="mt-1 text-sm text-zinc-700 dark:text-zinc-300">{{ $item['subtitle'] }}</p>
                                            @endif
                                            <div class="mt-2 flex flex-wrap items-center gap-4 text-sm text-zinc-600 dark:text-zinc-400">
                                                <span class="flex items-center gap-1">
                                                    <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                    </svg>
                                                    {{ $item['date']->format('M j, Y') }}
                                                </span>
                                                @if($item['time'])
                                                    <span class="flex items-center gap-1">
                                                        <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                        </svg>
                                                        {{ \Carbon\Carbon::parse($item['time'])->format('g:i A') }}
                                                    </span>
                                                @endif
                                                @if($item['location'])
                                                    <span class="flex items-center gap-1">
                                                        <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                        </svg>
                                                        {{ $item['location'] }}
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            @endif
                        @endforeach
                    </div>
                @else
                    <div class="py-12 text-center">
                        <svg class="mx-auto size-12 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        <p class="mt-4 text-sm font-medium text-zinc-900 dark:text-zinc-100">No upcoming activities</p>
                        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Check back soon for appointments and community events</p>
                    </div>
                @endif
            </div>
        </div>

    {{-- Scheduling Task Confirmation Modal --}}
    <flux:modal wire:model="showSchedulingTaskConfirmation" name="scheduling-task-confirmation">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Schedule This Task Instead?</flux:heading>
                <flux:subheading>
                    This is a scheduling task that hasn't been scheduled yet. We recommend using the "Schedule" button to book an appointment rather than marking it complete.
                </flux:subheading>
            </div>

            <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-900/20">
                <div class="flex items-start gap-3">
                    <svg class="size-5 shrink-0 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                    <p class="text-sm text-amber-900 dark:text-amber-200">
                        Are you sure you want to mark this as complete without scheduling the appointment?
                    </p>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 border-t border-zinc-200 pt-4 dark:border-zinc-700">
                <flux:button type="button" variant="ghost" wire:click="cancelTaskComplete">
                    Cancel
                </flux:button>
                <flux:button type="button" variant="primary" wire:click="confirmTaskComplete" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="confirmTaskComplete">Yes, Mark Complete</span>
                    <span wire:loading wire:target="confirmTaskComplete">Marking Complete...</span>
                </flux:button>
            </div>
        </div>
    </flux:modal>
    </div>
</div>
