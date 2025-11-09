<?php

namespace App\Providers;

use App\Models\PatientAppointment;
use App\Models\PatientAppointmentDocument;
use App\Observers\PatientAppointmentDocumentObserver;
use App\Observers\PatientAppointmentObserver;
use Illuminate\Support\ServiceProvider;
use Livewire\Volt\Volt;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Volt::mount([
            resource_path('views/livewire'),
            resource_path('views/pages'),
        ]);

        // Register model observers
        PatientAppointmentDocument::observe(PatientAppointmentDocumentObserver::class);
        PatientAppointment::observe(PatientAppointmentObserver::class);
    }
}
