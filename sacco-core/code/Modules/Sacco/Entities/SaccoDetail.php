<?php

namespace Modules\Sacco\Entities;

use Illuminate\Database\Eloquent\Model;

class SaccoDetail extends Model
{
    protected $table = 'sacco_details';

    protected $fillable = [
        'tagline',
        'mission',
        'vision',
        'about_text',
        'license_number',
        'registration_number',
        'founded_year',
        'paybill_number',
        'office_hours',
        'featured_services',
        'show_live_stats',
        'member_count_override',
        'branch_count_override',
        'facebook_url',
        'twitter_url',
        'instagram_url',
        'linkedin_url',
        'primary_color',
        'accent_color',
    ];

    protected $casts = [
        'featured_services' => 'array',
        'show_live_stats' => 'boolean',
        'founded_year' => 'integer',
        'member_count_override' => 'integer',
        'branch_count_override' => 'integer',
    ];
}
