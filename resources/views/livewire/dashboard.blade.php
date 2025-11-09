<?php

use App\Models\CommunityEvent;
use App\Models\Patient;
use App\Support\Helpers\DistanceCalculator;
use Illuminate\Support\Facades\Auth;
use function Livewire\Volt\{state, computed};

state(['patient' => function () {
    $user = Auth::user();
    if (!$user->patient) {
        return null;
    }

    return Patient::with([
        'appointments' => fn($q) => $q->where('date', '>=', today())->orderBy('date')->orderBy('time')->limit(3)->with('provider.system'),
        'tasks' => fn($q) => $q->whereNull('completed_at')->orderBy('created_at', 'desc')->limit(5)->with('scheduledAppointment'),
    ])->find($user->patient->id);
}]);

state(['upcomingAppointmentsCount' => fn() => Auth::user()->patient?->appointments()->where('date', '>=', today())->count() ?? 0]);
state(['activeTasksCount' => fn() => Auth::user()->patient?->tasks()->whereNull('completed_at')->count() ?? 0]);

$communityEvents = computed(function () {
    $patient = $this->patient;

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
        foreach ($patient->tasks as $task) {
            if ($task->description) {
                $keywords = $keywords->merge(str_word_count(strtolower($task->description), 1));
            }
        }

        // Filter out common words
        $commonWords = ['the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'with', 'from', 'by', 'of', 'is', 'are', 'was', 'were', 'be', 'been', 'being', 'have', 'has', 'had', 'do', 'does', 'did', 'will', 'would', 'should', 'could', 'may', 'might', 'must', 'can'];
        $keywords = $keywords->filter(fn($word) => strlen($word) > 3 && !in_array($word, $commonWords))->unique();

        // Filter events based on keywords
        if ($keywords->isNotEmpty()) {
            $events = $events->filter(function ($event) use ($keywords) {
                $eventText = strtolower($event->description . ' ' . $event->partner->name);
                foreach ($keywords as $keyword) {
                    if (str_contains($eventText, $keyword)) {
                        return true;
                    }
                }
                return false;
            });
        }
    }

    return $events->take(5);
});

$calculateDistance = function ($appointment) {
    $patient = $this->patient;
    if (!$patient || !$appointment->provider) {
        return null;
    }

    return DistanceCalculator::calculate(
        $patient->latitude,
        $patient->longitude,
        $appointment->provider->latitude,
        $appointment->provider->longitude
    );
};

$formatDistance = fn($distance) => DistanceCalculator::format($distance);

?>
<div class="flex h-full w-full flex-1 flex-col gap-6">
        {{-- Welcome Header --}}
        <div>
            <h1 class="text-2xl font-semibold text-zinc-900 dark:text-zinc-100">Welcome back, {{ Auth::user()->name }}</h1>
            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Here's what's happening with your healthcare journey</p>
        </div>

        {{-- Stats Cards --}}
        <div class="grid gap-4 md:grid-cols-3">
            <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
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
            </div>

            <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
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
            </div>

            <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-zinc-600 dark:text-zinc-400">Health Journey</p>
                        <p class="mt-2 text-sm font-medium text-zinc-900 dark:text-zinc-100">On Track</p>
                    </div>
                    <div class="rounded-full bg-purple-100 p-3 dark:bg-purple-900/30">
                        <svg class="size-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        {{-- Main Content Grid --}}
        <div class="grid flex-1 gap-6 lg:grid-cols-2">
            {{-- Upcoming Appointments --}}
            <div class="flex flex-col rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
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
                                <a href="{{ route('appointments.index') }}" class="block rounded-lg border border-zinc-200 p-4 transition hover:border-zinc-300 hover:shadow-sm dark:border-zinc-700 dark:hover:border-zinc-600">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1">
                                            <p class="font-medium text-zinc-900 dark:text-zinc-100">
                                                {{ $appointment->provider?->name ?? 'No provider assigned' }}
                                            </p>
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
            <div class="flex flex-col rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
                <div class="border-b border-zinc-200 p-6 dark:border-zinc-700">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Active Tasks</h2>
                        <a href="{{ route('tasks.index') }}" class="text-sm font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300">View all</a>
                    </div>
                </div>
                <div class="flex-1 p-6">
                    @if($patient && $patient->tasks->count() > 0)
                        <div class="space-y-3">
                            @foreach($patient->tasks as $task)
                                <div class="flex items-start gap-3 rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                                    <div class="flex size-5 shrink-0 items-center justify-center rounded border-2 border-zinc-300 dark:border-zinc-600"></div>
                                    <div class="flex-1">
                                        <a href="{{ route('tasks.index') }}" class="font-medium text-zinc-900 hover:text-zinc-700 dark:text-zinc-100 dark:hover:text-zinc-300">{{ $task->description }}</a>
                                        @if($task->instructions)
                                            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ Str::limit($task->instructions, 100) }}</p>
                                        @endif
                                        <div class="mt-2 flex flex-wrap items-center gap-2">
                                            @if($task->is_scheduling_task)
                                                <span class="inline-flex items-center rounded-full bg-blue-100 px-2 py-1 text-xs font-medium text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">
                                                    Scheduling Task
                                                </span>
                                                @if(!$task->scheduledAppointment)
                                                    <a href="{{ route('tasks.schedule', $task->id) }}" class="inline-flex items-center rounded-md bg-green-600 px-3 py-1 text-xs font-semibold text-white shadow-sm hover:bg-green-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-600">
                                                        <svg class="-ml-0.5 mr-1.5 size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                        </svg>
                                                        Schedule
                                                    </a>
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
            <a href="/timeline" class="block rounded-xl border-2 border-purple-200 bg-gradient-to-br from-purple-50 to-white p-6 shadow-sm transition hover:border-purple-300 hover:shadow-md dark:border-purple-800 dark:from-purple-900/20 dark:to-zinc-800">
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

        {{-- Community Events Feed --}}
        <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
            <div class="border-b border-zinc-200 p-6 dark:border-zinc-700">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Community Events</h2>
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Events recommended based on your health interests</p>
            </div>
            <div class="p-6">
                @if($this->communityEvents->count() > 0)
                    <div class="space-y-4">
                        @foreach($this->communityEvents as $event)
                            <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ $event->partner->name }}</p>
                                        <p class="mt-1 text-sm text-zinc-700 dark:text-zinc-300">{{ Str::limit($event->description, 120) }}</p>
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
                                                <span class="flex items-center gap-1">
                                                    <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                    </svg>
                                                    {{ $event->location }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="py-12 text-center">
                        <svg class="mx-auto size-12 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        <p class="mt-4 text-sm font-medium text-zinc-900 dark:text-zinc-100">No events available</p>
                        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Check back soon for community health events</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
