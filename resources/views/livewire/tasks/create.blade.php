<?php

use App\Models\PatientTask;
use Illuminate\Support\Facades\Auth;

use function Livewire\Volt\computed;
use function Livewire\Volt\layout;
use function Livewire\Volt\state;
use function Livewire\Volt\title;

layout('components.layouts.app');
title('Add Task');

state([
    'description' => '',
    'instructions' => '',
    'patient_appointment_id' => '',
    'is_scheduling_task' => false,
    'provider_specialty_needed' => '',
]);

$appointments = computed(fn () => Auth::user()->patient?->appointments()
    ->with('provider')
    ->orderBy('date', 'desc')
    ->get() ?? collect());

$save = function () {
    $this->authorize('create', PatientTask::class);

    $validated = $this->validate([
        'description' => ['required', 'string', 'max:255'],
        'instructions' => ['nullable', 'string'],
        'patient_appointment_id' => ['nullable', 'exists:patient_appointments,id'],
        'is_scheduling_task' => ['nullable', 'boolean'],
        'provider_specialty_needed' => ['nullable', 'string', 'max:255'],
    ], [
        'description.required' => 'Please enter a task description.',
        'description.max' => 'Task description must not exceed 255 characters.',
        'patient_appointment_id.exists' => 'The selected appointment is invalid.',
        'provider_specialty_needed.max' => 'Provider specialty must not exceed 255 characters.',
    ]);

    Auth::user()->patient->tasks()->create([
        'description' => $validated['description'],
        'instructions' => $validated['instructions'],
        'patient_appointment_id' => $validated['patient_appointment_id'] ?: null,
        'is_scheduling_task' => $validated['is_scheduling_task'] ?? false,
        'provider_specialty_needed' => $validated['provider_specialty_needed'],
    ]);

    session()->flash('success', 'Task created successfully!');

    $this->redirect(route('tasks.index'), navigate: true);
};

?>

<div class="flex h-full w-full flex-1 flex-col gap-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <flux:breadcrumbs>
                <flux:breadcrumbs.item href="{{ route('tasks.index') }}" icon="book-open-text">Tasks</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>Add Task</flux:breadcrumbs.item>
            </flux:breadcrumbs>
            <h1 class="mt-2 text-2xl font-semibold text-zinc-900 dark:text-zinc-100">Add Task</h1>
            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Create a new healthcare task</p>
        </div>
    </div>

    {{-- Form --}}
    <div class="mx-auto w-full max-w-2xl">
        <form wire:submit="save" class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="space-y-6">
                {{-- Description --}}
                <flux:field>
                    <flux:label>Task Description *</flux:label>
                    <flux:input wire:model="description" placeholder="e.g., Schedule follow-up MRI" />
                    <flux:error name="description" />
                </flux:field>

                {{-- Instructions --}}
                <flux:field>
                    <flux:label>Instructions</flux:label>
                    <flux:textarea wire:model="instructions" rows="4" placeholder="Additional details or instructions..." />
                    <flux:error name="instructions" />
                </flux:field>

                {{-- Link to Appointment --}}
                <flux:field>
                    <flux:label>Link to Appointment (Optional)</flux:label>
                    <flux:select wire:model="patient_appointment_id">
                        <option value="">No appointment</option>
                        @foreach($this->appointments as $appointment)
                            <option value="{{ $appointment->id }}">
                                {{ $appointment->date->format('M j, Y') }}
                                @if($appointment->provider)
                                    - {{ $appointment->provider->name }}
                                @endif
                            </option>
                        @endforeach
                    </flux:select>
                    <flux:error name="patient_appointment_id" />
                    <flux:description>Link this task to a specific appointment</flux:description>
                </flux:field>

                {{-- Scheduling Task Toggle --}}
                <flux:field>
                    <div class="flex items-center gap-3">
                        <flux:switch wire:model.live="is_scheduling_task" />
                        <flux:label>This is a scheduling task</flux:label>
                    </div>
                    <flux:description>Check if this task requires scheduling an appointment</flux:description>
                </flux:field>

                {{-- Provider Specialty (only show if scheduling task) --}}
                @if($is_scheduling_task)
                    <flux:field>
                        <flux:label>Provider Specialty Needed</flux:label>
                        <flux:input wire:model="provider_specialty_needed" placeholder="e.g., Cardiology, Radiology" />
                        <flux:error name="provider_specialty_needed" />
                        <flux:description>What type of provider do you need to see?</flux:description>
                    </flux:field>
                @endif

                {{-- Form Actions --}}
                <div class="flex items-center justify-between gap-3 border-t border-zinc-200 pt-6 dark:border-zinc-700">
                    <div wire:dirty class="text-sm text-amber-600 dark:text-amber-400">
                        <span class="inline-flex items-center gap-2">
                            <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                            </svg>
                            You have unsaved changes
                        </span>
                    </div>
                    <div class="flex items-center gap-3">
                        <flux:button type="button" variant="ghost" href="{{ route('tasks.index') }}">
                            Cancel
                        </flux:button>
                        <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="save">Create Task</span>
                            <span wire:loading wire:target="save">Creating...</span>
                        </flux:button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
