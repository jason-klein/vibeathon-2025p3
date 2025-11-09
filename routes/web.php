<?php

use App\Livewire\Settings\Appearance;
use App\Livewire\Settings\Password;
use App\Livewire\Settings\Profile;
use App\Livewire\Settings\TwoFactor;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Laravel\Fortify\Features;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Volt::route('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    // Patient features
    Volt::route('appointments', 'appointments.index')
        ->name('appointments.index');

    Volt::route('appointments/create', 'appointments.create')
        ->name('appointments.create');

    Volt::route('appointments/{appointmentId}', 'appointments.show')
        ->name('appointments.show');

    Volt::route('appointments/{appointmentId}/edit', 'appointments.edit')
        ->name('appointments.edit');

    Route::get('appointments/{appointment}/documents/{document}/download', function (\App\Models\PatientAppointment $appointment, \App\Models\PatientAppointmentDocument $document) {
        Gate::authorize('view', $appointment);

        if ($document->patient_appointment_id !== $appointment->id) {
            abort(404);
        }

        return Storage::disk('public')->download($document->file_path);
    })->name('appointments.documents.download');

    Volt::route('tasks', 'tasks.index')
        ->name('tasks.index');

    Volt::route('tasks/{taskId}/schedule', 'tasks.schedule')
        ->name('tasks.schedule');

    // Settings
    Route::redirect('settings', 'settings/profile');

    Route::get('settings/profile', Profile::class)->name('profile.edit');
    Route::get('settings/password', Password::class)->name('user-password.edit');
    Route::get('settings/appearance', Appearance::class)->name('appearance.edit');

    Route::get('settings/two-factor', TwoFactor::class)
        ->middleware(
            when(
                Features::canManageTwoFactorAuthentication()
                    && Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword'),
                ['password.confirm'],
                [],
            ),
        )
        ->name('two-factor.show');
});
