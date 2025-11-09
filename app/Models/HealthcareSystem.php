<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HealthcareSystem extends Model
{
    /** @use HasFactory<\Database\Factories\HealthcareSystemFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'is_preferred',
    ];

    protected function casts(): array
    {
        return [
            'is_preferred' => 'boolean',
        ];
    }

    public function providers(): HasMany
    {
        return $this->hasMany(HealthcareProvider::class);
    }
}
