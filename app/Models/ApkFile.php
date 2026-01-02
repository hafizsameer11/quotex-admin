<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApkFile extends Model
{
    protected $fillable = [
        'file_name',
        'file_path',
        'original_name',
        'version',
        'file_size',
        'is_active',
        'description',
        'uploaded_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'file_size' => 'integer',
    ];

    /**
     * Get the admin who uploaded this APK
     */
    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Get formatted file size
     */
    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->file_size;
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }

    /**
     * Scope to get active APK
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
