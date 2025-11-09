<?php

namespace App\Policies;

use App\Models\PatientAppointment;
use App\Models\User;

class PatientAppointmentPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->patient !== null;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, PatientAppointment $patientAppointment): bool
    {
        return $user->patient?->id === $patientAppointment->patient_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->patient !== null;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, PatientAppointment $patientAppointment): bool
    {
        return $user->patient?->id === $patientAppointment->patient_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, PatientAppointment $patientAppointment): bool
    {
        return $user->patient?->id === $patientAppointment->patient_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, PatientAppointment $patientAppointment): bool
    {
        return $user->patient?->id === $patientAppointment->patient_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, PatientAppointment $patientAppointment): bool
    {
        return $user->patient?->id === $patientAppointment->patient_id;
    }
}
