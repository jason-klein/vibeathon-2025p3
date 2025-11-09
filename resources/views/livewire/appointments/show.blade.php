<?php

use App\Models\PatientAppointment;
use App\Models\PatientTask;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

use function Livewire\Volt\computed;
use function Livewire\Volt\layout;
use function Livewire\Volt\mount;
use function Livewire\Volt\on;
use function Livewire\Volt\state;
use function Livewire\Volt\title;

layout('components.layouts.app');

state([
    'appointmentId',
    'editingNotes' => false,
    'patientNotes' => '',
    'showAddTaskModal' => false,
    'newTaskDescription' => '',
    'newTaskInstructions' => '',
    'showDeleteModal' => false,
]);

mount(function (string $appointmentId) {
    $this->appointmentId = $appointmentId;

    $appointment = PatientAppointment::findOrFail($appointmentId);
    $this->authorize('view', $appointment);

    $this->patientNotes = $appointment->patient_notes ?? '';
});

$appointment = computed(function () {
    return PatientAppointment::with(['provider.system', 'documents', 'tasks' => function ($query) {
        $query->orderBy('completed_at', 'asc')->orderBy('created_at', 'desc');
    }])
        ->findOrFail($this->appointmentId);
});

title(fn () => $this->appointment->provider?->name ?? 'Appointment Details');

$toggleEditNotes = function () {
    $this->editingNotes = !$this->editingNotes;

    if ($this->editingNotes) {
        $this->patientNotes = $this->appointment->patient_notes ?? '';
    }
};

$saveNotes = function () {
    $this->authorize('update', $this->appointment);

    $validated = $this->validate([
        'patientNotes' => ['nullable', 'string'],
    ]);

    $this->appointment->update([
        'patient_notes' => $validated['patientNotes'],
    ]);

    $this->editingNotes = false;

    session()->flash('success', 'Notes updated successfully!');
};

$cancelEditNotes = function () {
    $this->editingNotes = false;
    $this->patientNotes = $this->appointment->patient_notes ?? '';
};

$toggleTask = function (int $taskId) {
    $task = PatientTask::findOrFail($taskId);
    $this->authorize('update', $task);

    if ($task->patient_id !== Auth::user()->patient->id) {
        abort(403);
    }

    $task->update([
        'completed_at' => $task->completed_at ? null : now(),
    ]);
};

$openAddTaskModal = function () {
    $this->showAddTaskModal = true;
    $this->newTaskDescription = '';
    $this->newTaskInstructions = '';
};

$closeAddTaskModal = function () {
    $this->showAddTaskModal = false;
    $this->newTaskDescription = '';
    $this->newTaskInstructions = '';
};

$addTask = function () {
    $this->authorize('create', PatientTask::class);

    $validated = $this->validate([
        'newTaskDescription' => ['required', 'string', 'max:255'],
        'newTaskInstructions' => ['nullable', 'string'],
    ], [
        'newTaskDescription.required' => 'Please enter a task description.',
    ]);

    Auth::user()->patient->tasks()->create([
        'patient_appointment_id' => $this->appointmentId,
        'description' => $validated['newTaskDescription'],
        'instructions' => $validated['newTaskInstructions'],
        'is_scheduling_task' => false,
    ]);

    $this->closeAddTaskModal();

    session()->flash('success', 'Task added successfully!');
};

$openDeleteModal = function () {
    $this->showDeleteModal = true;
};

$closeDeleteModal = function () {
    $this->showDeleteModal = false;
};

$deleteAppointment = function () {
    $this->authorize('delete', $this->appointment);

    // Delete associated documents from storage
    foreach ($this->appointment->documents as $document) {
        if (Storage::disk('public')->exists($document->file_path)) {
            Storage::disk('public')->delete($document->file_path);
        }
    }

    // Delete the appointment (cascades to documents and tasks via database constraints if set up)
    $this->appointment->delete();

    session()->flash('success', 'Appointment deleted successfully!');

    $this->redirect(route('appointments.index'), navigate: true);
};

$downloadDocument = function (int $documentId) {
    $document = $this->appointment->documents->firstWhere('id', $documentId);

    if (!$document) {
        abort(404);
    }

    return Storage::disk('public')->download($document->file_path);
};

?>

<div class="flex h-full w-full flex-1 flex-col gap-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <flux:breadcrumbs>
                <flux:breadcrumbs.item href="{{ route('appointments.index') }}" icon="calendar">Appointments</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>Details</flux:breadcrumbs.item>
            </flux:breadcrumbs>
            <h1 class="mt-2 text-2xl font-semibold text-zinc-900 dark:text-zinc-100">
                {{ $this->appointment->provider?->name ?? 'Appointment Details' }}
            </h1>
        </div>
        <div class="flex items-center gap-3">
            <flux:button variant="danger" icon="trash" wire:click="openDeleteModal">
                Delete
            </flux:button>
            <flux:button variant="primary" icon="pencil" href="{{ route('appointments.edit', $this->appointment) }}">
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
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {{-- Left Column - Appointment Details --}}
        <div class="space-y-6 lg:col-span-2">
            {{-- Appointment Information --}}
            <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Appointment Information</h2>
                    @if($this->appointment->date < today())
                        <span class="inline-flex items-center rounded-full bg-zinc-100 px-3 py-1 text-xs font-medium text-zinc-700 dark:bg-zinc-700 dark:text-zinc-300">
                            Past Appointment
                        </span>
                    @elseif($this->appointment->date->isToday())
                        <span class="inline-flex items-center rounded-full bg-green-100 px-3 py-1 text-xs font-medium text-green-700 dark:bg-green-900/30 dark:text-green-400">
                            Today
                        </span>
                    @else
                        <span class="inline-flex items-center rounded-full bg-blue-100 px-3 py-1 text-xs font-medium text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">
                            Upcoming
                        </span>
                    @endif
                </div>

                <div class="space-y-4">
                    {{-- Date & Time --}}
                    <div class="flex items-start gap-3">
                        <svg class="size-5 text-zinc-500 dark:text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        <div>
                            <p class="font-medium text-zinc-900 dark:text-zinc-100">
                                {{ $this->appointment->date->format('l, F j, Y') }}
                            </p>
                            @if($this->appointment->time)
                                <p class="text-sm text-zinc-600 dark:text-zinc-400">
                                    {{ \Carbon\Carbon::parse($this->appointment->time)->format('g:i A') }}
                                </p>
                            @endif
                        </div>
                    </div>

                    {{-- Provider --}}
                    @if($this->appointment->provider)
                        <div class="flex items-start gap-3">
                            <svg class="size-5 text-zinc-500 dark:text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                            <div>
                                <p class="font-medium text-zinc-900 dark:text-zinc-100">
                                    {{ $this->appointment->provider->name }}
                                </p>
                                @if($this->appointment->provider->specialty)
                                    <p class="text-sm text-zinc-600 dark:text-zinc-400">
                                        {{ $this->appointment->provider->specialty }}
                                    </p>
                                @endif
                                @if($this->appointment->provider->system)
                                    <p class="text-sm text-zinc-600 dark:text-zinc-400">
                                        {{ $this->appointment->provider->system->name }}
                                    </p>
                                @endif
                            </div>
                        </div>
                    @endif

                    {{-- Location --}}
                    @if($this->appointment->location)
                        <div class="flex items-start gap-3">
                            <svg class="size-5 text-zinc-500 dark:text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            <p class="font-medium text-zinc-900 dark:text-zinc-100">
                                {{ $this->appointment->location }}
                            </p>
                        </div>
                    @endif

                    {{-- Summary --}}
                    @if($this->appointment->summary)
                        <div class="border-t border-zinc-200 pt-4 dark:border-zinc-700">
                            <h3 class="mb-2 text-sm font-medium text-zinc-700 dark:text-zinc-300">Reason for Visit</h3>
                            <p class="text-sm text-zinc-900 dark:text-zinc-100">
                                {{ $this->appointment->summary }}
                            </p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Patient Notes --}}
            <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">My Notes</h2>
                    @if(!$editingNotes)
                        <flux:button variant="ghost" size="sm" icon="pencil" wire:click="toggleEditNotes">
                            Edit
                        </flux:button>
                    @endif
                </div>

                @if($editingNotes)
                    <form wire:submit="saveNotes" class="space-y-4">
                        <flux:field>
                            <flux:textarea wire:model="patientNotes" rows="6" placeholder="Add your personal notes about this appointment..." />
                            <flux:error name="patientNotes" />
                        </flux:field>
                        <div class="flex items-center gap-3">
                            <flux:button type="submit" variant="primary" size="sm" wire:loading.attr="disabled">
                                <span wire:loading.remove wire:target="saveNotes">Save</span>
                                <span wire:loading wire:target="saveNotes">Saving...</span>
                            </flux:button>
                            <flux:button type="button" variant="ghost" size="sm" wire:click="cancelEditNotes">
                                Cancel
                            </flux:button>
                        </div>
                    </form>
                @else
                    @if($this->appointment->patient_notes)
                        <p class="whitespace-pre-wrap text-sm text-zinc-700 dark:text-zinc-300">
                            {{ $this->appointment->patient_notes }}
                        </p>
                    @else
                        <p class="text-sm italic text-zinc-500 dark:text-zinc-400">
                            No notes added yet. Click "Edit" to add your notes.
                        </p>
                    @endif
                @endif
            </div>

            {{-- Related Tasks --}}
            <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">
                        Related Tasks
                        @if($this->appointment->tasks->count() > 0)
                            <span class="ml-2 text-sm font-normal text-zinc-500 dark:text-zinc-400">
                                ({{ $this->appointment->tasks->count() }})
                            </span>
                        @endif
                    </h2>
                    <flux:button variant="primary" size="sm" icon="plus" wire:click="openAddTaskModal">
                        Add Task
                    </flux:button>
                </div>

                @if($this->appointment->tasks->count() > 0)
                    <div class="space-y-3">
                        @foreach($this->appointment->tasks as $task)
                            <div class="flex items-start gap-3 rounded-lg border border-zinc-200 p-3 dark:border-zinc-600">
                                <flux:checkbox
                                    wire:click="toggleTask({{ $task->id }})"
                                    :checked="$task->completed_at !== null"
                                />
                                <div class="flex-1">
                                    <p class="font-medium {{ $task->completed_at ? 'text-zinc-500 line-through dark:text-zinc-400' : 'text-zinc-900 dark:text-zinc-100' }}">
                                        {{ $task->description }}
                                    </p>
                                    @if($task->instructions)
                                        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                                            {{ $task->instructions }}
                                        </p>
                                    @endif
                                    @if($task->completed_at)
                                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                                            Completed {{ $task->completed_at->diffForHumans() }}
                                        </p>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm italic text-zinc-500 dark:text-zinc-400">
                        No tasks linked to this appointment yet.
                    </p>
                @endif
            </div>
        </div>

        {{-- Right Column - Documents --}}
        <div class="space-y-6">
            <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                <h2 class="mb-4 text-lg font-semibold text-zinc-900 dark:text-zinc-100">
                    Documents
                    @if($this->appointment->documents->count() > 0)
                        <span class="ml-2 text-sm font-normal text-zinc-500 dark:text-zinc-400">
                            ({{ $this->appointment->documents->count() }})
                        </span>
                    @endif
                </h2>

                @if($this->appointment->documents->count() > 0)
                    <div class="space-y-3">
                        @foreach($this->appointment->documents as $document)
                            <a
                                href="{{ route('appointments.documents.download', ['appointment' => $this->appointment, 'document' => $document]) }}"
                                class="flex items-center gap-3 rounded-lg border border-zinc-200 p-3 transition-colors hover:bg-zinc-50 dark:border-zinc-600 dark:hover:bg-zinc-700/50"
                            >
                                <svg class="size-8 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                </svg>
                                <div class="flex-1 min-w-0">
                                    <p class="truncate text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                        {{ basename($document->file_path) }}
                                    </p>
                                    @if($document->summary)
                                        <p class="mt-1 text-xs text-zinc-600 dark:text-zinc-400">
                                            {{ $document->summary }}
                                        </p>
                                    @endif
                                </div>
                                <svg class="size-5 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                </svg>
                            </a>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm italic text-zinc-500 dark:text-zinc-400">
                        No documents attached to this appointment.
                    </p>
                @endif
            </div>
        </div>
    </div>

    {{-- Add Task Modal --}}
    <flux:modal wire:model="showAddTaskModal" name="add-task">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Add New Task</flux:heading>
                <flux:subheading>Create a task related to this appointment</flux:subheading>
            </div>

            <form wire:submit="addTask" class="space-y-4">
                <flux:field>
                    <flux:label>Task Description *</flux:label>
                    <flux:input wire:model="newTaskDescription" placeholder="e.g., Schedule follow-up MRI" />
                    <flux:error name="newTaskDescription" />
                </flux:field>

                <flux:field>
                    <flux:label>Instructions</flux:label>
                    <flux:textarea wire:model="newTaskInstructions" rows="3" placeholder="Additional details or instructions..." />
                    <flux:error name="newTaskInstructions" />
                </flux:field>

                <div class="flex items-center justify-end gap-3 border-t border-zinc-200 pt-4 dark:border-zinc-700">
                    <flux:button type="button" variant="ghost" wire:click="closeAddTaskModal">
                        Cancel
                    </flux:button>
                    <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="addTask">Add Task</span>
                        <span wire:loading wire:target="addTask">Adding...</span>
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    {{-- Delete Confirmation Modal --}}
    <flux:modal wire:model="showDeleteModal" name="delete-appointment">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Delete Appointment?</flux:heading>
                <flux:subheading>This action cannot be undone. All related documents and tasks will also be deleted.</flux:subheading>
            </div>

            <div class="flex items-center justify-end gap-3 border-t border-zinc-200 pt-4 dark:border-zinc-700">
                <flux:button type="button" variant="ghost" wire:click="closeDeleteModal">
                    Cancel
                </flux:button>
                <flux:button type="button" variant="danger" wire:click="deleteAppointment" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="deleteAppointment">Delete Appointment</span>
                    <span wire:loading wire:target="deleteAppointment">Deleting...</span>
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
