<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class MiningCode extends Model
{
    protected $table = 'daily_mining_codes';
    
    protected $fillable = [
        'code',
        'code_type',
        'date',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date',
        'is_active' => 'boolean',
    ];

    /**
     * Get the admin who created this code
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope to get codes for today
     */
    public function scopeForToday($query)
    {
        return $query->where('date', Carbon::today())
                    ->where('is_active', true);
    }

    /**
     * Scope to get active codes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Check if code is valid for today
     */
    public function isValidForToday(): bool
    {
        return $this->is_active 
            && $this->date->isToday();
    }
}
