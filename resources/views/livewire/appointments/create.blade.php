<?php

use App\Models\HealthcareProvider;
use App\Models\PatientAppointment;
use App\Models\PatientAppointmentDocument;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

use function Livewire\Volt\computed;
use function Livewire\Volt\layout;
use function Livewire\Volt\state;
use function Livewire\Volt\title;
use function Livewire\Volt\uses;

uses([WithFileUploads::class]);

layout('components.layouts.app');
title('Add Appointment');

state([
    'date' => '',
    'time' => '',
    'healthcare_provider_id' => '',
    'location' => '',
    'summary' => '',
    'patient_notes' => '',
    'documents' => [],
]);

$providers = computed(fn () => HealthcareProvider::with('system')->orderBy('name')->get());

$isPastAppointment = fn () => $this->date && \Carbon\Carbon::parse($this->date)->isPast();

$save = function () {
    $this->authorize('create', PatientAppointment::class);

    $validated = $this->validate([
        'date' => ['required', 'date'],
        'time' => ['nullable', 'date_format:H:i'],
        'healthcare_provider_id' => [
            $this->isPastAppointment() ? 'required' : 'nullable',
            'exists:healthcare_providers,id',
        ],
        'location' => ['nullable', 'string', 'max:255'],
        'summary' => ['nullable', 'string'],
        'patient_notes' => [
            $this->isPastAppointment() ? 'required' : 'nullable',
            'string',
        ],
        'documents.*' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
    ], [
        'date.required' => 'Please select an appointment date.',
        'healthcare_provider_id.required' => 'Please select a provider for past appointments.',
        'healthcare_provider_id.exists' => 'The selected provider is invalid.',
        'patient_notes.required' => 'Please add notes for past appointments.',
        'documents.*.mimes' => 'Documents must be PDF, JPG, JPEG, or PNG files.',
        'documents.*.max' => 'Each document must not exceed 10MB.',
    ]);

    $appointment = Auth::user()->patient->appointments()->create([
        'date' => $validated['date'],
        'time' => $validated['time'] ?: null,
        'healthcare_provider_id' => $validated['healthcare_provider_id'] ?: null,
        'location' => $validated['location'],
        'summary' => $validated['summary'],
        'patient_notes' => $validated['patient_notes'],
    ]);

    // Handle document uploads
    if (! empty($this->documents)) {
        foreach ($this->documents as $document) {
            if ($document instanceof TemporaryUploadedFile) {
                $path = $document->store('appointment_docs', 'public');

                $appointment->documents()->create([
                    'file_path' => $path,
                    'summary' => null,
                ]);
            }
        }
    }

    session()->flash('success', 'Appointment created successfully!');

    $this->redirect(route('appointments.index'), navigate: true);
};

?>

<div class="flex h-full w-full flex-1 flex-col gap-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-zinc-900 dark:text-zinc-100">Add Appointment</h1>
            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Create a new healthcare appointment</p>
        </div>
    </div>

    {{-- Form --}}
    <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
        <form wire:submit="save" class="space-y-6">
            {{-- Date and Time --}}
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <flux:field>
                    <flux:label>Date *</flux:label>
                    <flux:input wire:model.live="date" type="date" />
                    <flux:error name="date" />
                </flux:field>

                <flux:field>
                    <flux:label>Time</flux:label>
                    <flux:input wire:model="time" type="time" />
                    <flux:error name="time" />
                </flux:field>
            </div>

            {{-- Provider --}}
            <flux:field>
                <flux:label>
                    Healthcare Provider
                    @if($this->isPastAppointment())
                        *
                    @endif
                </flux:label>
                <flux:select wire:model="healthcare_provider_id">
                    <option value="">Select a provider</option>
                    @foreach($this->providers as $provider)
                        <option value="{{ $provider->id }}">
                            {{ $provider->name }}
                            @if($provider->specialty)
                                ({{ $provider->specialty }})
                            @endif
                            @if($provider->system)
                                - {{ $provider->system->name }}
                            @endif
                        </option>
                    @endforeach
                </flux:select>
                <flux:error name="healthcare_provider_id" />
            </flux:field>

            {{-- Location --}}
            <flux:field>
                <flux:label>Location</flux:label>
                <flux:input wire:model="location" placeholder="e.g., 123 Main St, Suite 100" />
                <flux:error name="location" />
            </flux:field>

            {{-- Summary --}}
            <flux:field>
                <flux:label>Summary / Reason for Visit</flux:label>
                <flux:textarea wire:model="summary" rows="3" placeholder="Brief description of the appointment reason..." />
                <flux:error name="summary" />
            </flux:field>

            {{-- Patient Notes --}}
            <flux:field>
                <flux:label>
                    My Notes
                    @if($this->isPastAppointment())
                        *
                    @endif
                </flux:label>
                <flux:textarea wire:model="patient_notes" rows="4" placeholder="Your personal notes about this appointment..." />
                <flux:error name="patient_notes" />
                @if($this->isPastAppointment())
                    <flux:description>Notes are required for past appointments to document what occurred during the visit.</flux:description>
                @endif
            </flux:field>

            {{-- Documents Upload --}}
            <flux:field>
                <flux:label>Documents</flux:label>
                <input
                    type="file"
                    wire:model="documents"
                    multiple
                    accept=".pdf,.jpg,.jpeg,.png"
                    class="block w-full rounded-lg border border-zinc-300 text-sm text-zinc-900 file:mr-4 file:rounded-l-lg file:border-0 file:bg-zinc-100 file:px-4 file:py-2 file:text-sm file:font-medium file:text-zinc-900 hover:file:bg-zinc-200 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100 dark:file:bg-zinc-700 dark:file:text-zinc-100 dark:hover:file:bg-zinc-600"
                />
                <flux:description>Upload appointment-related documents (PDF, JPG, PNG - Max 10MB each)</flux:description>
                <flux:error name="documents.*" />

                {{-- Show uploading state --}}
                <div wire:loading wire:target="documents" class="mt-2 text-sm text-blue-600 dark:text-blue-400">
                    Uploading documents...
                </div>

                {{-- Show uploaded files --}}
                @if(count($documents) > 0)
                    <div class="mt-3 space-y-2">
                        @foreach($documents as $index => $document)
                            @if($document instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile)
                                <div class="flex items-center justify-between rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2 dark:border-zinc-600 dark:bg-zinc-800">
                                    <span class="text-sm text-zinc-700 dark:text-zinc-300">
                                        {{ $document->getClientOriginalName() }}
                                    </span>
                                </div>
                            @endif
                        @endforeach
                    </div>
                @endif
            </flux:field>

            {{-- Info Alert for Past Appointments --}}
            @if($this->isPastAppointment())
                <flux:callout variant="info">
                    <strong>Past Appointment:</strong> You're adding a past appointment. Please make sure to include the provider and your notes about the visit.
                </flux:callout>
            @endif

            {{-- Actions --}}
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
                    <flux:button variant="ghost" href="{{ route('appointments.index') }}">
                        Cancel
                    </flux:button>
                    <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="save">Create Appointment</span>
                        <span wire:loading wire:target="save">Creating...</span>
                    </flux:button>
                </div>
            </div>
        </form>
    </div>
</div>
