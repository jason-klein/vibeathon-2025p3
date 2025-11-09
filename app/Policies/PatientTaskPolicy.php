<?php

namespace App\Policies;

use App\Models\PatientTask;
use App\Models\User;

class PatientTaskPolicy
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
    public function view(User $user, PatientTask $patientTask): bool
    {
        return $user->patient?->id === $patientTask->patient_id;
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
    public function update(User $user, PatientTask $patientTask): bool
    {
        return $user->patient?->id === $patientTask->patient_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, PatientTask $patientTask): bool
    {
        return $user->patient?->id === $patientTask->patient_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, PatientTask $patientTask): bool
    {
        return $user->patient?->id === $patientTask->patient_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, PatientTask $patientTask): bool
    {
        return $user->patient?->id === $patientTask->patient_id;
    }
}
