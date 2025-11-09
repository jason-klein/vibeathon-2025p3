<?php

namespace App\Observers;

use App\Jobs\SummarizeAppointmentDocumentJob;
use App\Models\PatientAppointmentDocument;

class PatientAppointmentDocumentObserver
{
    /**
     * Handle the PatientAppointmentDocument "created" event.
     */
    public function created(PatientAppointmentDocument $patientAppointmentDocument): void
    {
        // Queue job to generate document executive summary
        SummarizeAppointmentDocumentJob::dispatch($patientAppointmentDocument);
    }
}
