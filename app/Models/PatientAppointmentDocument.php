<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientAppointmentDocument extends Model
{
    /** @use HasFactory<\Database\Factories\PatientAppointmentDocumentFactory> */
    use HasFactory;

    protected $fillable = [
        'patient_appointment_id',
        'file_path',
        'summary',
    ];

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(PatientAppointment::class, 'patient_appointment_id');
    }
}
