<?php

namespace Modules\Sacco\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Sacco\Services\SaccoDetailService;

class SaccoDetailController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', '2fa']);
        $this->middleware(['permission:sacco.details.edit'])->only(['edit', 'update']);
    }

    public function edit()
    {
        $detail = (new SaccoDetailService())->get();
        $defaultServices = (new SaccoDetailService())->defaultServices();

        return theme_view('sacco::detail.edit', compact('detail', 'defaultServices'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'tagline' => 'nullable|string|max:255',
            'mission' => 'nullable|string|max:1000',
            'vision' => 'nullable|string|max:1000',
            'about_text' => 'nullable|string|max:5000',
            'license_number' => 'nullable|string|max:100',
            'registration_number' => 'nullable|string|max:100',
            'founded_year' => 'nullable|integer|min:1900|max:' . date('Y'),
            'paybill_number' => 'nullable|string|max:20',
            'office_hours' => 'nullable|string|max:255',
            'show_live_stats' => 'nullable|boolean',
            'member_count_override' => 'nullable|integer|min:0',
            'branch_count_override' => 'nullable|integer|min:0',
            'facebook_url' => 'nullable|url|max:255',
            'twitter_url' => 'nullable|url|max:255',
            'instagram_url' => 'nullable|url|max:255',
            'linkedin_url' => 'nullable|url|max:255',
            'primary_color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'accent_color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'service_icon' => 'nullable|array',
            'service_title' => 'nullable|array',
            'service_description' => 'nullable|array',
        ]);

        $detail = (new SaccoDetailService())->get();

        $services = [];
        $icons = $request->input('service_icon', []);
        $titles = $request->input('service_title', []);
        $descriptions = $request->input('service_description', []);

        foreach ($titles as $index => $title) {
            $title = trim((string) $title);
            if ($title === '') {
                continue;
            }
            $services[] = [
                'icon' => $icons[$index] ?? 'fas fa-star',
                'title' => $title,
                'description' => trim((string) ($descriptions[$index] ?? '')),
            ];
        }

        $detail->fill($request->only([
            'tagline',
            'mission',
            'vision',
            'about_text',
            'license_number',
            'registration_number',
            'founded_year',
            'paybill_number',
            'office_hours',
            'member_count_override',
            'branch_count_override',
            'facebook_url',
            'twitter_url',
            'instagram_url',
            'linkedin_url',
            'primary_color',
            'accent_color',
        ]));

        $detail->show_live_stats = $request->boolean('show_live_stats');
        $detail->featured_services = !empty($services) ? $services : (new SaccoDetailService())->defaultServices();
        $detail->save();

        return redirect('sacco/detail/edit')->with('success', trans_choice('sacco::general.homepage_settings_saved', 1));
    }
}
