<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommunityPartner extends Model
{
    /** @use HasFactory<\Database\Factories\CommunityPartnerFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'is_nonprofit',
        'is_sponsor',
    ];

    protected function casts(): array
    {
        return [
            'is_nonprofit' => 'boolean',
            'is_sponsor' => 'boolean',
        ];
    }

    public function events(): HasMany
    {
        return $this->hasMany(CommunityEvent::class);
    }
}
