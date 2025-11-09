<?php

use function Livewire\Volt\state;
use function Livewire\Volt\on;

state(['show' => false]);
state(['message' => '']);
state(['type' => 'success']); // success, error, info

on(['toast' => function ($data) {
    $this->message = $data['message'] ?? '';
    $this->type = $data['type'] ?? 'success';
    $this->show = true;

    // Auto-hide after 4 seconds
    $this->dispatch('hide-toast-after-delay');
}]);

$hide = function () {
    $this->show = false;
};

?>

<div
    x-data="{
        show: @entangle('show'),
        autoHide() {
            setTimeout(() => {
                this.show = false;
                $wire.hide();
            }, 4000);
        }
    }"
    x-show="show"
    x-transition:enter="transform ease-out duration-300 transition"
    x-transition:enter-start="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
    x-transition:enter-end="translate-y-0 opacity-100 sm:translate-x-0"
    x-transition:leave="transition ease-in duration-100"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    @toast.window="autoHide()"
    class="pointer-events-none fixed inset-0 z-50 flex items-end justify-center px-4 py-6 sm:items-start sm:justify-end sm:p-6"
    style="display: none;"
>
    <div class="pointer-events-auto w-full max-w-sm overflow-hidden rounded-lg shadow-lg ring-1 ring-black ring-opacity-5
        {{ $type === 'success' ? 'bg-green-50 dark:bg-green-950' : '' }}
        {{ $type === 'error' ? 'bg-red-50 dark:bg-red-950' : '' }}
        {{ $type === 'info' ? 'bg-blue-50 dark:bg-blue-950' : '' }}
    ">
        <div class="p-4">
            <div class="flex items-start">
                <div class="shrink-0">
                    @if($type === 'success')
                        <svg class="size-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    @elseif($type === 'error')
                        <svg class="size-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    @else
                        <svg class="size-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    @endif
                </div>
                <div class="ml-3 w-0 flex-1 pt-0.5">
                    <p class="text-sm font-medium
                        {{ $type === 'success' ? 'text-green-900 dark:text-green-100' : '' }}
                        {{ $type === 'error' ? 'text-red-900 dark:text-red-100' : '' }}
                        {{ $type === 'info' ? 'text-blue-900 dark:text-blue-100' : '' }}
                    ">
                        {{ $message }}
                    </p>
                </div>
                <div class="ml-4 flex shrink-0">
                    <button
                        type="button"
                        @click="show = false; $wire.hide()"
                        class="inline-flex rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2
                            {{ $type === 'success' ? 'text-green-600 hover:text-green-800 focus:ring-green-500 dark:text-green-400 dark:hover:text-green-300' : '' }}
                            {{ $type === 'error' ? 'text-red-600 hover:text-red-800 focus:ring-red-500 dark:text-red-400 dark:hover:text-red-300' : '' }}
                            {{ $type === 'info' ? 'text-blue-600 hover:text-blue-800 focus:ring-blue-500 dark:text-blue-400 dark:hover:text-blue-300' : '' }}
                        "
                    >
                        <span class="sr-only">Close</span>
                        <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
