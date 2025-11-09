<?php

use App\Models\Patient;
use Illuminate\Support\Facades\Auth;
use function Livewire\Volt\{state};

state(['patient' => fn() => Auth::user()->patient()->with([
    'appointments' => fn($q) => $q->where('date', '>=', today())->orderBy('date')->orderBy('time')->limit(5)->with('provider.system'),
    'tasks' => fn($q) => $q->whereNull('completed_at')->orderBy('created_at', 'desc')->limit(5),
])->first()]);

state(['upcomingAppointmentsCount' => fn() => Auth::user()->patient?->appointments()->where('date', '>=', today())->count() ?? 0]);
state(['activeTasksCount' => fn() => Auth::user()->patient?->tasks()->whereNull('completed_at')->count() ?? 0]);

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
                                <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1">
                                            <p class="font-medium text-zinc-900 dark:text-zinc-100">
                                                {{ $appointment->provider?->name ?? 'No provider assigned' }}
                                            </p>
                                            @if($appointment->provider?->specialty)
                                                <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ $appointment->provider->specialty }}</p>
                                            @endif
                                            <div class="mt-2 flex items-center gap-4 text-sm text-zinc-600 dark:text-zinc-400">
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
                                            </div>
                                        </div>
                                    </div>
                                </div>
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
                                        <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ $task->description }}</p>
                                        @if($task->instructions)
                                            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ Str::limit($task->instructions, 100) }}</p>
                                        @endif
                                        @if($task->is_scheduling_task)
                                            <span class="mt-2 inline-flex items-center rounded-full bg-blue-100 px-2 py-1 text-xs font-medium text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">
                                                Scheduling Task
                                            </span>
                                        @endif
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
    </div>
</div>
