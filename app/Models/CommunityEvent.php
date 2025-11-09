<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommunityEvent extends Model
{
    /** @use HasFactory<\Database\Factories\CommunityEventFactory> */
    use HasFactory;

    protected $fillable = [
        'community_partner_id',
        'date',
        'time',
        'location',
        'description',
        'link',
        'is_partner_provided',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'time' => 'datetime:H:i',
            'is_partner_provided' => 'boolean',
        ];
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(CommunityPartner::class, 'community_partner_id');
    }
}
