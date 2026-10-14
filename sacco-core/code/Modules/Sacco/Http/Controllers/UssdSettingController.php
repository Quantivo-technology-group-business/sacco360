<?php

namespace Modules\Sacco\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Modules\Client\Entities\Client;
use Modules\Sacco\Entities\UssdMemberConfig;
use Modules\Sacco\Entities\UssdSession;
use Modules\Setting\Entities\Setting;

class UssdSettingController extends Controller
{
    /** Setting keys managed by this controller */
    private const SETTING_KEYS = [
        'ussd.enabled',
        'ussd.shortcode',
        'ussd.api_key',
        'ussd.daily_limit',
        'ussd.pin_attempts_max',
        'ussd.pin_length',
        'ussd.session_timeout',
        'ussd.welcome_message',
        'ussd.require_pin',
        'ussd.sms_pin_on_enable',
        'ussd.allowed_services',
    ];

    public function __construct()
    {
        $this->middleware(['auth', '2fa']);
        $this->middleware(['permission:sacco.ussd.settings'])->only(['settings', 'saveSettings']);
        $this->middleware(['permission:sacco.ussd.members'])->only(['members', 'sessions']);
        $this->middleware(['permission:sacco.ussd.members'])->only([
            'enable', 'disable', 'resetPin', 'setPin', 'setServices', 'unlock',
        ]);
    }

    // ── Global Settings ───────────────────────────────────────────────────

    public function settings()
    {
        $settings = $this->loadSettings();
        $stats = [
            'total_enabled'    => UssdMemberConfig::where('ussd_enabled', 1)->count(),
            'total_members'    => Client::count(),
            'sessions_today'   => UssdSession::whereDate('created_at', today())->count(),
            'sessions_month'   => UssdSession::whereMonth('created_at', now()->month)->count(),
            'locked_members'   => UssdMemberConfig::whereNotNull('locked_at')->count(),
        ];
        return theme_view('sacco::ussd.settings', compact('settings', 'stats'));
    }

    public function saveSettings(Request $request)
    {
        $request->validate([
            'ussd.shortcode'        => 'nullable|string|max:20',
            'ussd.api_key'          => 'nullable|string|max:255',
            'ussd.daily_limit'      => 'nullable|numeric|min:0',
            'ussd.pin_attempts_max' => 'nullable|integer|min:1|max:10',
            'ussd.pin_length'       => 'nullable|integer|in:4,5,6',
            'ussd.session_timeout'  => 'nullable|integer|min:30|max:600',
            'ussd.welcome_message'  => 'nullable|string|max:160',
        ]);

        foreach (self::SETTING_KEYS as $key) {
            $value = $request->input(str_replace('.', '_', $key), $request->input($key));
            // Handle checkboxes (boolean settings)
            if (in_array($key, ['ussd.enabled', 'ussd.require_pin', 'ussd.sms_pin_on_enable'])) {
                $value = $request->has(str_replace('.', '_', $key)) ? '1' : '0';
            }
            // Handle allowed_services array
            if ($key === 'ussd.allowed_services') {
                $value = json_encode($request->input('ussd_allowed_services', []));
            }
            if ($value !== null) {
                Setting::updateOrCreate(
                    ['setting_key' => $key],
                    ['setting_value' => $value, 'category' => 'ussd', 'displayed' => 1]
                );
            }
        }

        // Sync shortcode to sacco config env hint
        if ($request->filled('ussd_shortcode')) {
            Setting::updateOrCreate(
                ['setting_key' => 'ussd.shortcode'],
                ['setting_value' => $request->ussd_shortcode, 'category' => 'ussd', 'displayed' => 1]
            );
        }

        activity()->log('Update USSD Settings');
        \flash('USSD settings saved.')->success()->important();
        return redirect('sacco/ussd/settings');
    }

    // ── Member List ───────────────────────────────────────────────────────

    public function members(Request $request)
    {
        $search  = $request->s;
        $filter  = $request->filter; // 'enabled' | 'disabled' | 'locked' | ''
        $perPage = $request->per_page ?: 20;

        $query = Client::leftJoin('sacco_ussd_member_configs as uc', 'uc.client_id', 'clients.id')
            ->when($search, fn ($q) => $q->where(function ($q) use ($search) {
                $q->where('clients.first_name', 'like', "%$search%")
                  ->orWhere('clients.last_name', 'like', "%$search%")
                  ->orWhere('clients.mobile', 'like', "%$search%")
                  ->orWhere('clients.account_number', 'like', "%$search%");
            }))
            ->when($filter === 'enabled',  fn ($q) => $q->where('uc.ussd_enabled', 1))
            ->when($filter === 'disabled', fn ($q) => $q->where(function ($q) {
                $q->where('uc.ussd_enabled', 0)->orWhereNull('uc.id');
            }))
            ->when($filter === 'locked',   fn ($q) => $q->whereNotNull('uc.locked_at'))
            ->selectRaw("clients.id, clients.first_name, clients.last_name, clients.mobile,
                         clients.account_number, clients.status,
                         uc.ussd_enabled, uc.pin_set, uc.last_access_at,
                         uc.failed_pin_attempts, uc.locked_at")
            ->orderBy('clients.first_name')
            ->paginate($perPage)
            ->appends($request->input());

        return theme_view('sacco::ussd.members', compact('query', 'filter', 'search'));
    }

    // ── Per-member actions ────────────────────────────────────────────────

    public function enable(Request $request, int $clientId)
    {
        $client = Client::findOrFail($clientId);
        $config = UssdMemberConfig::firstOrNew(['client_id' => $clientId]);
        $config->ussd_enabled  = 1;
        $config->enabled_by_id = Auth::id();
        $config->enabled_at    = now();
        $config->notes         = $request->notes;
        // Default: all services allowed
        if (empty($config->allowed_services)) {
            $config->allowed_services = array_keys(UssdMemberConfig::ALL_SERVICES);
        }
        $config->save();

        activity()->on($client)->withProperties(['id' => $clientId])->log('Enable USSD Access');
        \flash('USSD access enabled for ' . $client->first_name . ' ' . $client->last_name)->success()->important();
        return redirect()->back();
    }

    public function disable(Request $request, int $clientId)
    {
        $client = Client::findOrFail($clientId);
        $config = UssdMemberConfig::where('client_id', $clientId)->first();
        if ($config) {
            $config->ussd_enabled = 0;
            $config->save();
        }
        activity()->on($client)->withProperties(['id' => $clientId])->log('Disable USSD Access');
        \flash('USSD access disabled.')->warning()->important();
        return redirect()->back();
    }

    public function resetPin(int $clientId)
    {
        $client = Client::findOrFail($clientId);
        $config = UssdMemberConfig::firstOrNew(['client_id' => $clientId]);
        $tempPin = str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        $config->pin_hash           = Hash::make($tempPin);
        $config->pin_set            = 0; // forces member to set a new PIN on next login
        $config->failed_pin_attempts = 0;
        $config->locked_at          = null;
        $config->save();

        // SMS the temp PIN if communication module available
        if (function_exists('send_sms') && $client->mobile) {
            $shortcode = Setting::where('setting_key', 'ussd.shortcode')->value('setting_value')
                         ?? config('sacco.ussd_shortcode', '*123#');
            send_sms($client->mobile, "Your USSD PIN has been reset. Temp PIN: {$tempPin}. Dial {$shortcode} and change it immediately.");
        }

        activity()->on($client)->withProperties(['id' => $clientId])->log('Reset USSD PIN');
        \flash("PIN reset. Temporary PIN: <strong>{$tempPin}</strong> — send this securely to the member.")->success()->important();
        return redirect()->back();
    }

    public function setPin(Request $request, int $clientId)
    {
        $request->validate([
            'pin' => ['required', 'digits:4', 'confirmed'],
        ]);
        $client = Client::findOrFail($clientId);
        $config = UssdMemberConfig::firstOrNew(['client_id' => $clientId]);
        $config->pin_hash            = Hash::make($request->pin);
        $config->pin_set             = 1;
        $config->failed_pin_attempts = 0;
        $config->locked_at           = null;
        $config->save();

        activity()->on($client)->withProperties(['id' => $clientId])->log('Admin Set USSD PIN');
        \flash('USSD PIN updated successfully.')->success()->important();
        return redirect()->back();
    }

    public function setServices(Request $request, int $clientId)
    {
        $client = Client::findOrFail($clientId);
        $config = UssdMemberConfig::firstOrNew(['client_id' => $clientId]);
        $config->allowed_services        = $request->input('allowed_services', []);
        $config->daily_transaction_limit = $request->daily_transaction_limit ?: null;
        $config->save();

        activity()->on($client)->withProperties(['id' => $clientId])->log('Update USSD Services');
        \flash('USSD service permissions updated.')->success()->important();
        return redirect()->back();
    }

    public function unlock(int $clientId)
    {
        $client = Client::findOrFail($clientId);
        $config = UssdMemberConfig::where('client_id', $clientId)->firstOrFail();
        $config->failed_pin_attempts = 0;
        $config->locked_at           = null;
        $config->save();

        activity()->on($client)->withProperties(['id' => $clientId])->log('Unlock USSD Account');
        \flash('USSD account unlocked for ' . $client->first_name . '.')->success()->important();
        return redirect()->back();
    }

    // ── Session Log ───────────────────────────────────────────────────────

    public function sessions(Request $request)
    {
        $perPage = $request->per_page ?: 30;
        $search  = $request->s;
        $date    = $request->date;

        $sessions = UssdSession::with('client')
            ->when($search, fn ($q) => $q->where('phone_number', 'like', "%$search%")
                ->orWhere('session_id', 'like', "%$search%"))
            ->when($date, fn ($q) => $q->whereDate('created_at', $date))
            ->orderByDesc('id')
            ->paginate($perPage)
            ->appends($request->input());

        return theme_view('sacco::ussd.sessions', compact('sessions'));
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    private function loadSettings(): array
    {
        $rows = Setting::where('category', 'ussd')->pluck('setting_value', 'setting_key')->toArray();
        $defaults = [
            'ussd.enabled'          => '1',
            'ussd.shortcode'        => config('sacco.ussd_shortcode', '*123#'),
            'ussd.api_key'          => config('sacco.ussd_api_key', ''),
            'ussd.daily_limit'      => '50000',
            'ussd.pin_attempts_max' => '3',
            'ussd.pin_length'       => '4',
            'ussd.session_timeout'  => '120',
            'ussd.welcome_message'  => 'Welcome to SACCO Mobile Banking',
            'ussd.require_pin'      => '1',
            'ussd.sms_pin_on_enable'=> '1',
            'ussd.allowed_services' => json_encode(array_keys(UssdMemberConfig::ALL_SERVICES)),
        ];
        return array_merge($defaults, $rows);
    }
}
