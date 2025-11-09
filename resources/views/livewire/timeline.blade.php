<?php

use App\Models\Patient;
use Illuminate\Support\Facades\Auth;

use function Livewire\Volt\computed;
use function Livewire\Volt\layout;
use function Livewire\Volt\title;

layout('components.layouts.app');
title('Your Health Timeline');

$patient = computed(function () {
    $user = Auth::user();
    if (! $user->patient) {
        return null;
    }

    return Patient::with([
        'appointments' => fn ($q) => $q
            ->where('date', '<', today())
            ->orderBy('date', 'desc')
            ->orderBy('time', 'desc')
            ->with(['provider.system', 'documents', 'tasks' => fn ($tq) => $tq->orderBy('created_at', 'desc')]),
    ])->find($user->patient->id);
});

?>

<div class="flex h-full w-full flex-1 flex-col gap-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-zinc-900 dark:text-zinc-100">Your Health Timeline</h1>
            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">A complete view of your healthcare journey</p>
        </div>
        <div class="flex items-center gap-3">
            <flux:button variant="ghost" icon="arrow-left" href="{{ route('dashboard') }}">
                Back to Dashboard
            </flux:button>
        </div>
    </div>

    @if(!$this->patient)
        <div class="rounded-xl border border-zinc-200 bg-white p-12 text-center dark:border-zinc-700 dark:bg-zinc-800">
            <p class="text-zinc-600 dark:text-zinc-400">No patient record found.</p>
        </div>
    @else
        {{-- Plain English Patient Record --}}
        @if($this->patient->plain_english_record)
            <div class="rounded-xl border-2 border-purple-200 bg-gradient-to-br from-purple-50 to-white p-8 shadow-sm dark:border-purple-800 dark:from-purple-900/20 dark:to-zinc-800">
                <div class="mb-4 flex items-center gap-3">
                    <div class="rounded-full bg-purple-100 p-3 dark:bg-purple-900/30">
                        <svg class="size-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">Your Health Story in Plain English</h2>
                        @if($this->patient->executive_summary_updated_at)
                            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Last updated {{ $this->patient->executive_summary_updated_at->diffForHumans() }}</p>
                        @endif
                    </div>
                </div>
                <div class="prose prose-sm max-w-none text-zinc-700 dark:prose-invert dark:text-zinc-300">
                    {!! nl2br(e($this->patient->plain_english_record)) !!}
                </div>
            </div>
        @endif

        {{-- Executive Summary --}}
        @if($this->patient->executive_summary)
            <div class="rounded-xl border border-blue-200 bg-gradient-to-br from-blue-50 to-white p-6 shadow-sm dark:border-blue-800 dark:from-blue-900/20 dark:to-zinc-800">
                <div class="mb-3 flex items-center gap-3">
                    <div class="rounded-full bg-blue-100 p-2 dark:bg-blue-900/30">
                        <svg class="size-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                        </svg>
                    </div>
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Current Health Summary</h2>
                </div>
                <div class="prose prose-sm max-w-none text-zinc-700 dark:prose-invert dark:text-zinc-300">
                    {!! nl2br(e($this->patient->executive_summary)) !!}
                </div>
            </div>
        @endif

        {{-- Timeline Section --}}
        <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
            <div class="border-b border-zinc-200 p-6 dark:border-zinc-700">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Healthcare Encounters</h2>
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Your past appointments and visits</p>
            </div>

            @if($this->patient->appointments->count() > 0)
                <div class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @foreach($this->patient->appointments as $appointment)
                        <div class="p-6">
                            {{-- Timeline Date Marker --}}
                            <div class="mb-4 flex items-center gap-3">
                                <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-green-100 dark:bg-green-900/30">
                                    <svg class="size-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">
                                        {{ $appointment->date->format('F j, Y') }}
                                    </p>
                                    @if($appointment->time)
                                        <p class="text-sm text-zinc-600 dark:text-zinc-400">
                                            {{ \Carbon\Carbon::parse($appointment->time)->format('g:i A') }}
                                        </p>
                                    @endif
                                </div>
                            </div>

                            {{-- Appointment Details --}}
                            <div class="ml-13 space-y-4">
                                {{-- Provider Info --}}
                                @if($appointment->provider)
                                    <div>
                                        <p class="font-semibold text-zinc-900 dark:text-zinc-100">
                                            {{ $appointment->provider->name }}
                                        </p>
                                        @if($appointment->provider->specialty)
                                            <p class="text-sm text-zinc-600 dark:text-zinc-400">
                                                {{ $appointment->provider->specialty }}
                                            </p>
                                        @endif
                                        @if($appointment->provider->system)
                                            <p class="text-sm text-zinc-600 dark:text-zinc-400">
                                                {{ $appointment->provider->system->name }}
                                            </p>
                                        @endif
                                    </div>
                                @endif

                                {{-- Appointment Executive Summary (AI-Generated) --}}
                                @if($appointment->executive_summary)
                                    <div class="rounded-lg border-2 border-blue-200 bg-gradient-to-br from-blue-50 to-white p-4 shadow-sm dark:border-blue-800 dark:from-blue-900/20 dark:to-zinc-800">
                                        <div class="mb-2 flex items-center gap-2">
                                            <svg class="size-4 text-blue-600 dark:text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z"></path>
                                            </svg>
                                            <h4 class="text-sm font-semibold text-blue-900 dark:text-blue-200">Appointment Summary</h4>
                                            <span class="ml-auto text-xs font-medium text-blue-700 dark:text-blue-400">AI-Generated</span>
                                        </div>
                                        <div class="prose prose-sm max-w-none text-zinc-700 dark:prose-invert dark:text-zinc-300">
                                            {!! nl2br(e($appointment->executive_summary)) !!}
                                        </div>
                                    </div>
                                @else
                                    <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-3 dark:border-zinc-700 dark:bg-zinc-800/50">
                                        <p class="text-xs italic text-zinc-500 dark:text-zinc-400">
                                            Appointment summary generating...
                                        </p>
                                    </div>
                                @endif

                                {{-- Visit Summary --}}
                                @if($appointment->summary)
                                    <div class="rounded-lg border border-zinc-300 bg-white p-4 shadow-sm dark:border-zinc-600 dark:bg-zinc-800">
                                        <h4 class="mb-2 text-sm font-semibold text-zinc-800 dark:text-zinc-200">Visit Summary</h4>
                                        <p class="whitespace-pre-wrap text-sm leading-relaxed text-zinc-950 dark:text-white">
                                            {{ $appointment->summary }}
                                        </p>
                                    </div>
                                @endif

                                {{-- Patient Notes --}}
                                @if($appointment->patient_notes)
                                    <div class="rounded-lg border border-zinc-300 bg-white p-4 shadow-sm dark:border-zinc-600 dark:bg-zinc-800">
                                        <h4 class="mb-2 text-sm font-semibold text-zinc-800 dark:text-zinc-200">My Notes</h4>
                                        <p class="whitespace-pre-wrap text-sm leading-relaxed text-zinc-950 dark:text-white">
                                            {{ $appointment->patient_notes }}
                                        </p>
                                    </div>
                                @endif

                                {{-- Documents --}}
                                @if($appointment->documents->count() > 0)
                                    <div>
                                        <h4 class="mb-2 text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                            Documents ({{ $appointment->documents->count() }})
                                        </h4>
                                        <div class="space-y-2">
                                            @foreach($appointment->documents as $document)
                                                <a
                                                    href="{{ route('appointments.documents.download', ['appointment' => $appointment, 'document' => $document]) }}"
                                                    class="flex items-center gap-3 rounded-lg border border-zinc-200 p-3 transition-colors hover:bg-zinc-100 dark:border-zinc-700 dark:hover:bg-zinc-800"
                                                >
                                                    <svg class="size-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                                    </svg>
                                                    <div class="flex-1 min-w-0">
                                                        <p class="truncate text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                                            {{ basename($document->file_path) }}
                                                        </p>
                                                        @if($document->summary)
                                                            <p class="text-xs text-zinc-600 dark:text-zinc-400">
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
                                    </div>
                                @endif

                                {{-- Related Tasks --}}
                                @if($appointment->tasks->count() > 0)
                                    <div>
                                        <h4 class="mb-2 text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                            Related Tasks ({{ $appointment->tasks->count() }})
                                        </h4>
                                        <div class="space-y-2">
                                            @foreach($appointment->tasks as $task)
                                                <div class="flex items-start gap-3 rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                                                    <div class="flex size-5 shrink-0 items-center justify-center rounded border-2 {{ $task->completed_at ? 'border-green-500 bg-green-500' : 'border-zinc-300 dark:border-zinc-600' }}">
                                                        @if($task->completed_at)
                                                            <svg class="size-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                                                            </svg>
                                                        @endif
                                                    </div>
                                                    <div class="flex-1">
                                                        <p class="text-sm font-medium {{ $task->completed_at ? 'text-zinc-500 line-through dark:text-zinc-400' : 'text-zinc-900 dark:text-zinc-100' }}">
                                                            {{ $task->description }}
                                                        </p>
                                                        @if($task->instructions)
                                                            <p class="mt-1 text-xs text-zinc-600 dark:text-zinc-400">
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
                                    </div>
                                @endif

                                {{-- Link to Full Details --}}
                                <div>
                                    <a href="{{ route('appointments.show', $appointment) }}" class="inline-flex items-center gap-1 text-sm font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300">
                                        View full appointment details
                                        <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                        </svg>
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="flex items-center justify-center py-12 text-center">
                    <div>
                        <svg class="mx-auto size-12 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        <p class="mt-4 text-sm font-medium text-zinc-900 dark:text-zinc-100">No past appointments yet</p>
                        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Your healthcare encounters will appear here as they're recorded</p>
                    </div>
                </div>
            @endif
        </div>
    @endif
</div>
