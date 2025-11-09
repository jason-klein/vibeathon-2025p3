<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PatientAppointment extends Model
{
    /** @use HasFactory<\Database\Factories\PatientAppointmentFactory> */
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'healthcare_provider_id',
        'date',
        'time',
        'location',
        'summary',
        'patient_notes',
        'scheduled_from_task_id',
        'executive_summary',
        'confirmation_number',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'time' => 'datetime:H:i',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (PatientAppointment $appointment) {
            if (empty($appointment->confirmation_number)) {
                $appointment->confirmation_number = self::generateConfirmationNumber();
            }
        });
    }

    public static function generateConfirmationNumber(?string $timestamp = null): string
    {
        $time = $timestamp ?? now()->format('Y-m-d H:i:s.u');
        $hash = strtoupper(substr(md5($time), 0, 6));

        return "APT-{$hash}";
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(HealthcareProvider::class, 'healthcare_provider_id');
    }

    public function scheduledFromTask(): BelongsTo
    {
        return $this->belongsTo(PatientTask::class, 'scheduled_from_task_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(PatientAppointmentDocument::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(PatientTask::class);
    }
}
