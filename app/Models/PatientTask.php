<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PatientTask extends Model
{
    /** @use HasFactory<\Database\Factories\PatientTaskFactory> */
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'patient_appointment_id',
        'description',
        'instructions',
        'is_scheduling_task',
        'provider_specialty_needed',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'is_scheduling_task' => 'boolean',
            'completed_at' => 'datetime',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(PatientAppointment::class, 'patient_appointment_id');
    }

    public function scheduledAppointment(): HasOne
    {
        return $this->hasOne(PatientAppointment::class, 'scheduled_from_task_id');
    }
}
