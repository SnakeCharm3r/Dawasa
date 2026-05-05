<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'hospital_name',
        'logo_path',
        'favicon_path',
        'address',
        'phone',
        'email',
        'website',
        'description',
        'social_media',
        'timezone',
        'date_format',
        'currency',
        'items_per_page',
        'maintenance_mode',
    ];

    protected $casts = [
        'social_media' => 'array',
        'maintenance_mode' => 'boolean',
        'items_per_page' => 'integer',
    ];

    /**
     * Get the singleton instance of system preferences
     */
    public static function getInstance(): self
    {
        return static::firstOrCreate([], [
            'hospital_name' => 'Hospital Management System',
            'timezone' => 'UTC',
            'date_format' => 'Y-m-d',
            'currency' => 'USD',
            'items_per_page' => 10,
            'maintenance_mode' => false,
        ]);
    }
}
