<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HealthcareProviderAvailability extends Model
{
    /** @use HasFactory<\Database\Factories\HealthcareProviderAvailabilityFactory> */
    use HasFactory;

    protected $fillable = [
        'healthcare_provider_id',
        'date',
        'time',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'time' => 'datetime:H:i',
        ];
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(HealthcareProvider::class, 'healthcare_provider_id');
    }
}
