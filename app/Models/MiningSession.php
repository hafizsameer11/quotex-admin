<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MiningSession extends Model
{
    protected $fillable = [
        'user_id',
        'started_at',
        'stopped_at',
        'status',
        'progress',
        'rewards_claimed',
        'investment_id',
        'used_code',
        'code_date',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'stopped_at' => 'datetime',
        'rewards_claimed' => 'boolean',
        'code_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function investment()
    {
        return $this->belongsTo(Investment::class);
    }

    public function claimedAmounts()
    {
        return $this->hasMany(ClaimedAmount::class, 'investment_id', 'investment_id');
    }

    public function getRewardsEarnedAttribute()
    {
        return $this->claimedAmounts()->sum('amount');
    }
}
