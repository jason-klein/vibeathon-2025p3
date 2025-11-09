<?php

use App\Models\PatientTask;
use Illuminate\Support\Facades\Auth;

use function Livewire\Volt\computed;
use function Livewire\Volt\layout;
use function Livewire\Volt\state;
use function Livewire\Volt\title;

layout('components.layouts.app');
title('Tasks');

state(['filter' => 'active']);

$tasks = computed(function () {
    $patient = Auth::user()->patient;

    if (! $patient) {
        return collect();
    }

    $query = $patient->tasks()->with(['appointment.provider', 'scheduledAppointment']);

    if ($this->filter === 'active') {
        $query->whereNull('completed_at');
    } elseif ($this->filter === 'completed') {
        $query->whereNotNull('completed_at');
    }

    return $query->orderBy('created_at', 'desc')->get();
});

$toggleComplete = function ($taskId) {
    $task = PatientTask::findOrFail($taskId);

    if ($task->patient_id !== Auth::user()->patient->id) {
        return;
    }

    $task->update([
        'completed_at' => $task->completed_at ? null : now(),
    ]);
};

?>

<div class="flex h-full w-full flex-1 flex-col gap-6">
        {{-- Header --}}
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-zinc-900 dark:text-zinc-100">My Tasks</h1>
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Track and complete your healthcare tasks</p>
            </div>
            <flux:button variant="primary" href="{{ route('tasks.create') }}" icon="plus">
                Add Task
            </flux:button>
        </div>

        {{-- Filter Tabs --}}
        <div class="border-b border-zinc-200 dark:border-zinc-700">
            <nav class="-mb-px flex gap-6">
                <button
                    wire:click="$set('filter', 'active')"
                    class="border-b-2 px-1 py-4 text-sm font-medium transition-colors {{ $filter === 'active' ? 'border-blue-600 text-blue-600 dark:border-blue-400 dark:text-blue-400' : 'border-transparent text-zinc-600 hover:border-zinc-300 hover:text-zinc-900 dark:text-zinc-400 dark:hover:border-zinc-600 dark:hover:text-zinc-100' }}"
                >
                    <span wire:loading.remove wire:target="filter">Active</span>
                    <span wire:loading wire:target="filter" class="inline-flex items-center gap-2">
                        <svg class="size-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        Active
                    </span>
                </button>
                <button
                    wire:click="$set('filter', 'completed')"
                    class="border-b-2 px-1 py-4 text-sm font-medium transition-colors {{ $filter === 'completed' ? 'border-blue-600 text-blue-600 dark:border-blue-400 dark:text-blue-400' : 'border-transparent text-zinc-600 hover:border-zinc-300 hover:text-zinc-900 dark:text-zinc-400 dark:hover:border-zinc-600 dark:hover:text-zinc-100' }}"
                >
                    <span wire:loading.remove wire:target="filter">Completed</span>
                    <span wire:loading wire:target="filter" class="inline-flex items-center gap-2">
                        <svg class="size-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        Completed
                    </span>
                </button>
                <button
                    wire:click="$set('filter', 'all')"
                    class="border-b-2 px-1 py-4 text-sm font-medium transition-colors {{ $filter === 'all' ? 'border-blue-600 text-blue-600 dark:border-blue-400 dark:text-blue-400' : 'border-transparent text-zinc-600 hover:border-zinc-300 hover:text-zinc-900 dark:text-zinc-400 dark:hover:border-zinc-600 dark:hover:text-zinc-100' }}"
                >
                    <span wire:loading.remove wire:target="filter">All</span>
                    <span wire:loading wire:target="filter" class="inline-flex items-center gap-2">
                        <svg class="size-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        All
                    </span>
                </button>
            </nav>
        </div>

        {{-- Tasks List --}}
        <div class="space-y-3">
            @forelse($this->tasks as $task)
                <a
                    href="{{ route('tasks.show', $task) }}"
                    class="block rounded-xl border border-zinc-200 bg-white p-6 transition-shadow hover:shadow-lg dark:border-zinc-700 dark:bg-zinc-800 dark:hover:shadow-zinc-900/50 {{ $task->completed_at ? 'opacity-75' : '' }}"
                >
                    <div class="flex items-start gap-4">
                        {{-- Checkbox --}}
                        <button
                            wire:click.stop="toggleComplete({{ $task->id }})"
                            wire:loading.attr="disabled"
                            wire:target="toggleComplete({{ $task->id }})"
                            class="mt-0.5 flex size-6 shrink-0 items-center justify-center rounded border-2 transition-colors {{ $task->completed_at ? 'border-green-600 bg-green-600' : 'border-zinc-300 hover:border-green-600 dark:border-zinc-600 dark:hover:border-green-600' }}"
                        >
                            <span wire:loading.remove wire:target="toggleComplete({{ $task->id }})">
                                @if($task->completed_at)
                                    <svg class="size-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                @endif
                            </span>
                            <svg wire:loading wire:target="toggleComplete({{ $task->id }})" class="size-4 animate-spin text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                        </button>

                        {{-- Task Content --}}
                        <div class="flex-1">
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex-1">
                                    <h3 class="font-semibold text-zinc-900 dark:text-zinc-100 {{ $task->completed_at ? 'line-through' : '' }}">
                                        {{ $task->description }}
                                    </h3>

                                    @if($task->instructions)
                                        <p class="mt-2 text-sm text-zinc-700 dark:text-zinc-300">{{ $task->instructions }}</p>
                                    @endif

                                    {{-- Task Meta --}}
                                    <div class="mt-3 flex flex-wrap items-center gap-2">
                                        @if($task->is_scheduling_task)
                                            <span class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">
                                                <svg class="mr-1 size-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                </svg>
                                                Scheduling Task
                                            </span>
                                            @if(!$task->scheduledAppointment)
                                                <a href="{{ route('tasks.schedule', $task->id) }}" onclick="event.stopPropagation();" class="inline-flex items-center rounded-md bg-green-600 px-3 py-1 text-xs font-semibold text-white shadow-sm hover:bg-green-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-600">
                                                    <svg class="-ml-0.5 mr-1.5 size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                    </svg>
                                                    Schedule
                                                </a>
                                            @endif
                                        @endif

                                        @if($task->provider_specialty_needed)
                                            <span class="inline-flex items-center rounded-full bg-purple-100 px-2.5 py-0.5 text-xs font-medium text-purple-700 dark:bg-purple-900/30 dark:text-purple-400">
                                                {{ $task->provider_specialty_needed }}
                                            </span>
                                        @endif

                                        @if($task->appointment)
                                            <span class="inline-flex items-center text-xs text-zinc-600 dark:text-zinc-400">
                                                Related to: {{ $task->appointment->provider?->name ?? 'Appointment' }}
                                            </span>
                                        @endif

                                        @if($task->scheduledAppointment)
                                            <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-700 dark:bg-green-900/30 dark:text-green-400">
                                                <svg class="mr-1 size-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                </svg>
                                                Appointment Scheduled
                                            </span>
                                        @endif
                                    </div>

                                    @if($task->completed_at)
                                        <p class="mt-2 text-xs text-zinc-600 dark:text-zinc-400">
                                            Completed {{ $task->completed_at->diffForHumans() }}
                                        </p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
            @empty
                <div class="flex flex-col items-center justify-center rounded-xl border border-zinc-200 bg-white py-12 dark:border-zinc-700 dark:bg-zinc-800">
                    <svg class="size-16 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                    <p class="mt-4 text-lg font-medium text-zinc-900 dark:text-zinc-100">
                        @if($filter === 'active')
                            No active tasks
                        @elseif($filter === 'completed')
                            No completed tasks
                        @else
                            No tasks yet
                        @endif
                    </p>
                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                        @if($filter === 'active')
                            You're all caught up! Great work!
                        @elseif($filter === 'completed')
                            Your completed tasks will appear here
                        @else
                            Tasks will be added as part of your healthcare journey
                        @endif
                    </p>
                </div>
            @endforelse
        </div>
</div>
