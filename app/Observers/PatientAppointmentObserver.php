<?php

namespace App\Observers;

use App\Jobs\UpdateAppointmentExecutiveSummaryJob;
use App\Models\PatientAppointment;

class PatientAppointmentObserver
{
    /**
     * Handle the PatientAppointment "updated" event.
     */
    public function updated(PatientAppointment $patientAppointment): void
    {
        // Check if meaningful fields have changed that would warrant a summary update
        $relevantFields = ['summary', 'patient_notes', 'date', 'time', 'location', 'healthcare_provider_id'];

        if ($patientAppointment->wasChanged($relevantFields)) {
            // Queue job to regenerate appointment executive summary
            UpdateAppointmentExecutiveSummaryJob::dispatch($patientAppointment);
        }
    }
}
