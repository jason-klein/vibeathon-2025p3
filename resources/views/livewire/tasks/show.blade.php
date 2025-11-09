<?php

use App\Models\PatientTask;

use function Livewire\Volt\computed;
use function Livewire\Volt\layout;
use function Livewire\Volt\mount;
use function Livewire\Volt\state;
use function Livewire\Volt\title;

layout('components.layouts.app');

state([
    'taskId',
    'showDeleteModal' => false,
]);

mount(function (string $taskId) {
    $this->taskId = $taskId;

    $task = PatientTask::findOrFail($taskId);
    $this->authorize('view', $task);
});

$task = computed(function () {
    return PatientTask::with(['appointment.provider.system', 'scheduledAppointment.provider'])
        ->findOrFail($this->taskId);
});

title(fn () => $this->task->description);

$toggleComplete = function () {
    $this->authorize('update', $this->task);

    $this->task->update([
        'completed_at' => $this->task->completed_at ? null : now(),
    ]);

    session()->flash('success', $this->task->completed_at ? 'Task marked as complete!' : 'Task marked as incomplete!');
};

$openDeleteModal = function () {
    $this->showDeleteModal = true;
};

$closeDeleteModal = function () {
    $this->showDeleteModal = false;
};

$deleteTask = function () {
    $this->authorize('delete', $this->task);

    $this->task->delete();

    session()->flash('success', 'Task deleted successfully!');

    $this->redirect(route('tasks.index'), navigate: true);
};

?>

<div class="flex h-full w-full flex-1 flex-col gap-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <flux:breadcrumbs>
                <flux:breadcrumbs.item href="{{ route('tasks.index') }}" icon="book-open-text">Tasks</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>Details</flux:breadcrumbs.item>
            </flux:breadcrumbs>
            <h1 class="mt-2 text-2xl font-semibold text-zinc-900 dark:text-zinc-100">
                Task Details
            </h1>
        </div>
        <div class="flex items-center gap-3">
            <flux:button variant="danger" icon="trash" wire:click="openDeleteModal">
                Delete
            </flux:button>
            <flux:button variant="primary" icon="pencil" href="{{ route('tasks.edit', $this->task) }}">
                Edit
            </flux:button>
        </div>
    </div>

    {{-- Flash Messages --}}
    @if (session('success'))
        <flux:callout variant="success">
            {{ session('success') }}
        </flux:callout>
    @endif

    {{-- Main Content --}}
    <div class="mx-auto w-full max-w-3xl space-y-6">
        {{-- Task Information --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Task Information</h2>
                @if($this->task->completed_at)
                    <span class="inline-flex items-center rounded-full bg-green-100 px-3 py-1 text-xs font-medium text-green-700 dark:bg-green-900/30 dark:text-green-400">
                        <svg class="mr-1.5 size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Completed
                    </span>
                @else
                    <span class="inline-flex items-center rounded-full bg-blue-100 px-3 py-1 text-xs font-medium text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">
                        Pending
                    </span>
                @endif
            </div>

            <div class="space-y-4">
                {{-- Description --}}
                <div>
                    <h3 class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Description</h3>
                    <p class="mt-1 text-zinc-900 dark:text-zinc-100">{{ $this->task->description }}</p>
                </div>

                {{-- Instructions --}}
                @if($this->task->instructions)
                    <div class="border-t border-zinc-200 pt-4 dark:border-zinc-700">
                        <h3 class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Instructions</h3>
                        <p class="mt-1 whitespace-pre-wrap text-sm text-zinc-700 dark:text-zinc-300">
                            {{ $this->task->instructions }}
                        </p>
                    </div>
                @endif

                {{-- Task Type & Specialty --}}
                @if($this->task->is_scheduling_task || $this->task->provider_specialty_needed)
                    <div class="border-t border-zinc-200 pt-4 dark:border-zinc-700">
                        <div class="flex flex-wrap items-center gap-2">
                            @if($this->task->is_scheduling_task)
                                <span class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">
                                    <svg class="mr-1 size-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                    Scheduling Task
                                </span>
                            @endif
                            @if($this->task->provider_specialty_needed)
                                <span class="inline-flex items-center rounded-full bg-purple-100 px-2.5 py-0.5 text-xs font-medium text-purple-700 dark:bg-purple-900/30 dark:text-purple-400">
                                    {{ $this->task->provider_specialty_needed }}
                                </span>
                            @endif
                        </div>
                    </div>
                @endif

                {{-- Linked Appointment --}}
                @if($this->task->appointment)
                    <div class="border-t border-zinc-200 pt-4 dark:border-zinc-700">
                        <h3 class="mb-2 text-sm font-medium text-zinc-700 dark:text-zinc-300">Related to Appointment</h3>
                        <a
                            href="{{ route('appointments.show', $this->task->appointment) }}"
                            class="flex items-center gap-3 rounded-lg border border-zinc-200 p-3 transition-colors hover:bg-zinc-50 dark:border-zinc-600 dark:hover:bg-zinc-700/50"
                        >
                            <svg class="size-8 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            <div class="flex-1">
                                <p class="font-medium text-zinc-900 dark:text-zinc-100">
                                    {{ $this->task->appointment->date->format('l, F j, Y') }}
                                </p>
                                @if($this->task->appointment->provider)
                                    <p class="text-sm text-zinc-600 dark:text-zinc-400">
                                        {{ $this->task->appointment->provider->name }}
                                    </p>
                                @endif
                            </div>
                            <svg class="size-5 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </a>
                    </div>
                @endif

                {{-- Scheduled Appointment --}}
                @if($this->task->scheduledAppointment)
                    <div class="border-t border-zinc-200 pt-4 dark:border-zinc-700">
                        <h3 class="mb-2 text-sm font-medium text-zinc-700 dark:text-zinc-300">Scheduled Appointment</h3>
                        <a
                            href="{{ route('appointments.show', $this->task->scheduledAppointment) }}"
                            class="flex items-center gap-3 rounded-lg border border-green-200 bg-green-50 p-3 transition-colors hover:bg-green-100 dark:border-green-800 dark:bg-green-900/20 dark:hover:bg-green-900/30"
                        >
                            <svg class="size-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            <div class="flex-1">
                                <p class="font-medium text-green-900 dark:text-green-100">
                                    {{ $this->task->scheduledAppointment->date->format('l, F j, Y') }}
                                </p>
                                @if($this->task->scheduledAppointment->provider)
                                    <p class="text-sm text-green-700 dark:text-green-300">
                                        {{ $this->task->scheduledAppointment->provider->name }}
                                    </p>
                                @endif
                            </div>
                            <svg class="size-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </a>
                    </div>
                @elseif($this->task->is_scheduling_task && !$this->task->completed_at)
                    <div class="border-t border-zinc-200 pt-4 dark:border-zinc-700">
                        <a
                            href="{{ route('tasks.schedule', $this->task) }}"
                            class="inline-flex items-center gap-2 rounded-lg bg-green-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 dark:bg-green-600 dark:hover:bg-green-700"
                        >
                            <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            Schedule Appointment
                        </a>
                    </div>
                @endif

                {{-- Completed At --}}
                @if($this->task->completed_at)
                    <div class="border-t border-zinc-200 pt-4 dark:border-zinc-700">
                        <h3 class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Completed</h3>
                        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                            {{ $this->task->completed_at->format('l, F j, Y \a\t g:i A') }}
                            ({{ $this->task->completed_at->diffForHumans() }})
                        </p>
                    </div>
                @endif
            </div>

            {{-- Toggle Complete Button --}}
            <div class="mt-6 border-t border-zinc-200 pt-6 dark:border-zinc-700">
                @if($this->task->completed_at)
                    <flux:button wire:click="toggleComplete" variant="ghost" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="toggleComplete">Mark as Incomplete</span>
                        <span wire:loading wire:target="toggleComplete">Updating...</span>
                    </flux:button>
                @else
                    <flux:button wire:click="toggleComplete" variant="primary" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="toggleComplete">Mark as Complete</span>
                        <span wire:loading wire:target="toggleComplete">Updating...</span>
                    </flux:button>
                @endif
            </div>
        </div>
    </div>

    {{-- Delete Confirmation Modal --}}
    <flux:modal wire:model="showDeleteModal" name="delete-task">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Delete Task?</flux:heading>
                <flux:subheading>This action cannot be undone.</flux:subheading>
            </div>

            <div class="flex items-center justify-end gap-3 border-t border-zinc-200 pt-4 dark:border-zinc-700">
                <flux:button type="button" variant="ghost" wire:click="closeDeleteModal">
                    Cancel
                </flux:button>
                <flux:button type="button" variant="danger" wire:click="deleteTask" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="deleteTask">Delete Task</span>
                    <span wire:loading wire:target="deleteTask">Deleting...</span>
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
