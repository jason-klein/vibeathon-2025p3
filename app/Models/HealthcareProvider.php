<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HealthcareProvider extends Model
{
    /** @use HasFactory<\Database\Factories\HealthcareProviderFactory> */
    use HasFactory;

    protected $fillable = [
        'healthcare_system_id',
        'name',
        'specialty',
        'location',
        'latitude',
        'longitude',
        'phone',
        'email',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
        ];
    }

    public function system(): BelongsTo
    {
        return $this->belongsTo(HealthcareSystem::class, 'healthcare_system_id');
    }

    public function availability(): HasMany
    {
        return $this->hasMany(HealthcareProviderAvailability::class);
    }
}
