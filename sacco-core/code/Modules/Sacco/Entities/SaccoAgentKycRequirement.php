<?php

namespace Modules\Sacco\Entities;

use Illuminate\Database\Eloquent\Model;

class SaccoAgentKycRequirement extends Model
{
    protected $fillable = [
        'code',
        'name',
        'category',
        'description',
        'is_mandatory',
        'requires_verification',
        'active',
        'sort_order',
    ];

    protected $casts = [
        'is_mandatory' => 'boolean',
        'requires_verification' => 'boolean',
        'active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopeMandatory($query)
    {
        return $query->where('is_mandatory', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function getCategoryLabelAttribute(): string
    {
        $labels = [
            'identity' => 'Identity Documents',
            'tax' => 'Tax Documents',
            'biometric' => 'Biometric',
            'address' => 'Address Verification',
            'business' => 'Business Documents',
            'background' => 'Background Check',
            'reference' => 'References',
            'financial' => 'Financial Documents',
            'other' => 'Other',
        ];

        return $labels[$this->category] ?? ucfirst($this->category);
    }

    public static function getCategories(): array
    {
        return [
            'identity' => 'Identity Documents',
            'tax' => 'Tax Documents',
            'biometric' => 'Biometric',
            'address' => 'Address Verification',
            'business' => 'Business Documents',
            'background' => 'Background Check',
            'reference' => 'References',
            'financial' => 'Financial Documents',
            'other' => 'Other',
        ];
    }
}
