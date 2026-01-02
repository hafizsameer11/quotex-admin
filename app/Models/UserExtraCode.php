<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class UserExtraCode extends Model
{
    protected $table = 'user_extra_codes';
    
    protected $fillable = [
        'user_id',
        'code',
        'code_date',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'code_date' => 'date',
        'is_active' => 'boolean',
    ];

    /**
     * Get the user this code is assigned to
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

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
        return $query->where('code_date', Carbon::today())
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
            && $this->code_date->isToday();
    }
}
