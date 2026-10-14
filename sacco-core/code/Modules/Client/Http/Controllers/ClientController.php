<?php

namespace Modules\Client\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Laracasts\Flash\Flash;
use Modules\Branch\Entities\Branch;
use Modules\Client\Entities\Client;
use Modules\Client\Entities\ClientType;
use Modules\Client\Entities\ClientUser;
use Modules\Client\Entities\Profession;
use Modules\Client\Entities\Title;
use Modules\Core\Entities\Country;
use Modules\CustomField\Entities\CustomField;
use Modules\User\Entities\User;
use Modules\User\Filters\HasRoleFilter;
use Modules\User\Services\AuthorizationService;
use Modules\User\Traits\AuthorizesWithScope;
use Modules\User\Entities\Role;
use Yajra\DataTables\Facades\DataTables;

class ClientController extends Controller
{
    use AuthorizesWithScope, AuthorizesRequests;

    public function __construct()
    {
        $this->middleware(['auth', '2fa']);
        $this->middleware(['role_or_permission:client.clients.index|client.clients.index_own'])->only(['index', 'show', 'get_clients', 'member360Index', 'member360Show', 'onboard']);
        $this->middleware(['permission:client.clients.create'])->only(['create', 'store']);
        $this->middleware(['role_or_permission:client.clients.edit|client.clients.edit_own'])->only(['edit', 'update']);
        $this->middleware(['role_or_permission:client.clients.destroy|client.clients.destroy_own'])->only(['destroy']);
        $this->middleware(['permission:client.clients.user.create'])->only(['store_user', 'create_user']);
        $this->middleware(['permission:client.clients.user.destroy'])->only(['destroy_user']);
        $this->middleware(['permission:client.clients.activate'])->only(['change_status']);

    }

    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index(Request $request)
    {
        $perPage = $request->per_page ?: 20;
        $orderBy = $request->order_by;
        $orderByDir = $request->order_by_dir;
        $search = $request->s;
        $status = $request->status;

        $hasMemberNumber = Schema::hasColumn('clients', 'member_number');

        $query = Client::leftJoin("branches", "branches.id", "clients.branch_id")
            ->leftJoin("users", "users.id", "clients.loan_officer_id")
            ->leftJoin("client_identification", "client_identification.client_id", "clients.id")
            ->when($orderBy, function (Builder $query) use ($orderBy, $orderByDir) {
                $query->orderBy($orderBy, $orderByDir);
            })
            ->when($search, function (Builder $query) use ($search, $hasMemberNumber) {
                $query->where(function (Builder $q) use ($search, $hasMemberNumber) {
                    $q->where('clients.first_name', 'like', "%$search%")
                        ->orWhere('clients.last_name', 'like', "%$search%")
                        ->orWhere('clients.account_number', 'like', "%$search%")
                        ->orWhere('clients.mobile', 'like', "%$search%")
                        ->orWhere('clients.phone', 'like', "%$search%")
                        ->orWhere('clients.external_id', 'like', "%$search%")
                        ->orWhere('clients.email', 'like', "%$search%")
                        ->orWhere('clients.id', $search);
                    if ($hasMemberNumber) {
                        $q->orWhere('clients.member_number', 'like', "%$search%");
                    }
                    $q->orWhere('client_identification.identification_value', 'like', "%$search%");
                });
            })
            ->when($status, function ($query) use ($status) {
                $query->where('clients.status', $status);
            })
            ->groupBy('clients.id');
        $query = $this->applyAuthorizationScopes($query, 'client.clients.index', 'clients.branch_id', 'clients.loan_officer_id');

        $statsQuery = Client::query();
        $statsQuery = $this->applyAuthorizationScopes($statsQuery, 'client.clients.index', 'clients.branch_id', 'clients.loan_officer_id');
        $stats = [
            'total' => (clone $statsQuery)->count(),
            'active' => (clone $statsQuery)->where('status', 'active')->count(),
            'pending' => (clone $statsQuery)->where('status', 'pending')->count(),
            'inactive' => (clone $statsQuery)->whereIn('status', ['inactive', 'deceased'])->count(),
        ];

        $data = $query
            ->selectRaw("branches.name branch,concat(users.first_name,' ',users.last_name) staff,clients.id,clients.branch_id,clients.loan_officer_id,clients.first_name,clients.last_name,clients.gender,clients.mobile,clients.email,clients.external_id,clients.account_number,clients.photo,clients.status")
            ->paginate($perPage)
            ->appends($request->input());
        return theme_view('client::client.index', compact('data', 'stats'));
    }

    public function member360Index(Request $request)
    {
        $perPage = $request->per_page ?: 20;
        $query = $this->buildClientListQuery($request);

        if ($request->arrears && class_exists(\Modules\Sacco\Services\Member360Service::class)) {
            $arrearsClientIds = DB::table('loan_repayment_schedules')
                ->join('loans', 'loans.id', '=', 'loan_repayment_schedules.loan_id')
                ->whereIn('loans.status', ['active', 'disbursed'])
                ->where('loan_repayment_schedules.due_date', '<', date('Y-m-d'))
                ->whereRaw('(loan_repayment_schedules.principal + loan_repayment_schedules.interest + loan_repayment_schedules.fees + loan_repayment_schedules.penalties - loan_repayment_schedules.principal_repaid_derived - loan_repayment_schedules.interest_repaid_derived - loan_repayment_schedules.fees_repaid_derived - loan_repayment_schedules.penalties_repaid_derived) > 0')
                ->distinct()
                ->pluck('loans.client_id');
            $query->whereIn('clients.id', $arrearsClientIds);
        }

        $scopedQuery = Client::query();
        $scopedQuery = $this->applyAuthorizationScopes($scopedQuery, 'client.clients.index', 'clients.branch_id', 'clients.loan_officer_id');

        $portfolio = ['members' => 0, 'total_savings' => 0, 'total_shares' => 0, 'total_loans' => 0, 'total_arrears' => 0, 'net_position' => 0];
        if (class_exists(\Modules\Sacco\Services\Member360Service::class)) {
            $scopedIds = (clone $scopedQuery)->pluck('id')->toArray();
            $portfolio = (new \Modules\Sacco\Services\Member360Service())->getPortfolioTotals($scopedIds);
        }

        $memberNumberCol = Schema::hasColumn('clients', 'member_number') ? 'clients.member_number,' : '';
        $kycCol = Schema::hasColumn('clients', 'kyc_verified') ? 'clients.kyc_verified' : '0 as kyc_verified';

        $data = $query
            ->selectRaw("branches.name branch,concat(users.first_name,' ',users.last_name) staff,clients.id,clients.branch_id,clients.loan_officer_id,clients.first_name,clients.last_name,{$memberNumberCol}clients.external_id,clients.mobile,clients.photo,clients.status,{$kycCol}")
            ->paginate($perPage)
            ->appends($request->input());

        $summaries = [];
        if (class_exists(\Modules\Sacco\Services\Member360Service::class) && $data->count()) {
            $summaries = (new \Modules\Sacco\Services\Member360Service())->getBulkSummaries($data->pluck('id')->toArray());
        }

        return theme_view('client::member360.index', compact('data', 'summaries', 'portfolio'));
    }

    public function member360Show($id)
    {
        $client = Client::with(['loan_officer', 'branch', 'title', 'profession'])->find($id);
        if (!$client) {
            \flash(trans_choice('client::general.client', 1) . ' not found')->error()->important();
            return redirect('client/member360');
        }
        $this->authorize('view', $client);

        if (!class_exists(\Modules\Sacco\Services\Member360Service::class)) {
            \flash('Member 360 is not available.')->error()->important();
            return redirect('client/' . $id . '/show');
        }

        $service = new \Modules\Sacco\Services\Member360Service();
        $member360 = $service->getSummary($id);
        $timeline = $service->getActivityTimeline($id, 15);

        return theme_view('client::member360.show', compact('client', 'member360', 'timeline'));
    }

    protected function buildClientListQuery(Request $request): Builder
    {
        $orderBy = $request->order_by;
        $orderByDir = $request->order_by_dir;
        $search = $request->s;
        $status = $request->status;

        $hasMemberNumber = Schema::hasColumn('clients', 'member_number');

        $query = Client::leftJoin('branches', 'branches.id', 'clients.branch_id')
            ->leftJoin('users', 'users.id', 'clients.loan_officer_id')
            ->leftJoin('client_identification', 'client_identification.client_id', 'clients.id')
            ->when($orderBy, function (Builder $query) use ($orderBy, $orderByDir) {
                $query->orderBy($orderBy, $orderByDir);
            })
            ->when($search, function (Builder $query) use ($search, $hasMemberNumber) {
                $query->where(function (Builder $q) use ($search, $hasMemberNumber) {
                    $q->where('clients.first_name', 'like', "%$search%")
                        ->orWhere('clients.last_name', 'like', "%$search%")
                        ->orWhere('clients.account_number', 'like', "%$search%")
                        ->orWhere('clients.mobile', 'like', "%$search%")
                        ->orWhere('clients.phone', 'like', "%$search%")
                        ->orWhere('clients.external_id', 'like', "%$search%")
                        ->orWhere('clients.email', 'like', "%$search%")
                        ->orWhere('clients.id', $search);
                    if ($hasMemberNumber) {
                        $q->orWhere('clients.member_number', 'like', "%$search%");
                    }
                    $q->orWhere('client_identification.identification_value', 'like', "%$search%");
                });
            })
            ->when($status, function ($query) use ($status) {
                $query->where('clients.status', $status);
            })
            ->groupBy('clients.id');

        return $this->applyAuthorizationScopes($query, 'client.clients.index', 'clients.branch_id', 'clients.loan_officer_id');
    }

    public function get_clients(Request $request)
    {

        $status = $request->status;
        $query = DB::table("clients")
            ->leftJoin("branches", "branches.id", "clients.branch_id")
            ->leftJoin("users", "users.id", "clients.loan_officer_id")
            ->selectRaw("branches.name branch,concat(users.first_name,' ',users.last_name) staff,clients.id,clients.branch_id,clients.loan_officer_id,concat(clients.first_name,' ',clients.last_name) name,clients.gender,clients.mobile,clients.email,clients.external_id,clients.status")
            ->when($status, function ($query) use ($status) {
                $query->where('status', $status);
            });
        $query = app(HasRoleFilter::class)->apply($query, Auth::user(), 'client.clients.index', 'clients.branch_id', 'clients.loan_officer_id');
        return DataTables::of($query)->editColumn('staff', function ($data) {
            return $data->staff;
        })->editColumn('action', function ($data) {
            $action = '<div class="btn-group"><button type="button" class="btn btn-info btn-xs dropdown-toggle" data-toggle="dropdown" aria-expanded="true"><i class="fa fa-navicon"></i></button> <ul class="dropdown-menu dropdown-menu-right" role="menu">';
            $action .= '<li><a href="' . url('client/' . $data->id . '/show') . '" class="">' . trans_choice('user::general.detail', 2) . '</a></li>';
            if (Auth::user()->hasPermissionTo('client.clients.edit')) {
                $action .= '<li><a href="' . url('client/' . $data->id . '/edit') . '" class="">' . trans_choice('user::general.edit', 2) . '</a></li>';
            }
            if (Auth::user()->hasPermissionTo('client.clients.destroy')) {
                $action .= '<li><a href="' . url('client/' . $data->id . '/destroy') . '" class="confirm">' . trans_choice('user::general.delete', 2) . '</a></li>';
            }
            $action .= "</ul></li></div>";
            return $action;
        })->editColumn('id', function ($data) {
            return '<a href="' . url('client/' . $data->id . '/show') . '">' . $data->id . '</a>';

        })->editColumn('name', function ($data) {
            return '<a href="' . url('client/' . $data->id . '/show') . '">' . $data->name . '</a>';

        })->editColumn('gender', function ($data) {
            if ($data->gender == "male") {
                return trans_choice('core::general.male', 1);
            }
            if ($data->gender == "female") {
                return trans_choice('core::general.female', 1);
            }
            if ($data->gender == "other") {
                return trans_choice('core::general.other', 1);
            }
            if ($data->gender == "unspecified") {
                return trans_choice('core::general.unspecified', 1);
            }
        })->editColumn('status', function ($data) {
            if ($data->status == "pending") {
                return trans_choice('core::general.pending', 1);
            }
            if ($data->status == "active") {
                return trans_choice('core::general.active', 1);
            }
            if ($data->status == "inactive") {
                return trans_choice('core::general.inactive', 1);
            }
            if ($data->gender == "deceased") {
                return trans_choice('client::general.deceased', 1);
            }
            if ($data->gender == "unspecified") {
                return trans_choice('core::general.unspecified', 1);
            }
        })->rawColumns(['id', 'name', 'action'])->make(true);
    }

    /**
     * Show the form for creating a new resource.
     * @return Response
     */
    public function create()
    {
        $titles = Title::all();
        $professions = Profession::all();
        $client_types = ClientType::all();
        $users = User::whereHas('roles', function ($query) {
            return $query->where('name', '!=', 'client');
        })->get();
        $branches = Branch::all();
        $countries = Country::all();
        $custom_fields = CustomField::where('category', 'add_client')->where('active', 1)->get();
        $sacco_groups = class_exists(\Modules\Sacco\Entities\SaccoGroup::class) ? \Modules\Sacco\Entities\SaccoGroup::all() : collect();
        return theme_view('client::client.create', compact('titles', 'professions', 'client_types', 'users', 'branches', 'countries', 'custom_fields', 'sacco_groups'));
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        $is_organisation = false;
        if ($request->client_type_id) {
            $client_type = ClientType::find($request->client_type_id);
            if ($client_type && strtolower($client_type->name) === 'organisation') {
                $is_organisation = true;
            }
        }

        if ($is_organisation) {
            $request->validate([
                'first_name' => ['required'], // Organisation Name
                'registration_number' => ['required'],
                'registration_date' => ['required', 'date'],
                'branch_id' => ['required'],
                'email' => ['nullable','email', 'max:255'],
                'created_date' => ['required', 'date'],
            ]);
        } else {
            $request->validate([
                'first_name' => ['required'], // Full Name
                'last_name' => ['required'],
                'gender' => ['required'],
                'branch_id' => ['required'],
                'email' => ['nullable','email', 'max:255'],
                'dob' => ['required', 'date'],
                'created_date' => ['required', 'date'],
                'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png'],
            ]);
        }

        $client = new Client();
        $client->first_name = $request->first_name;
        $client->branch_id = $request->branch_id;
        $client->client_type_id = $request->client_type_id;
        $client->created_by_id = Auth::id();
        $client->created_date = $request->created_date;
        $client->email = $request->email;
        $client->mobile = $request->mobile;
        $client->alternative_mobile = $request->alternative_mobile;
        $client->address = $request->address;
        $client->zip = $request->postal_code;
        $client->landing_site = $request->landing_site;
        $client->years_in_business = $request->years_in_business;
        $client->notes = $request->notes;

        if ($is_organisation) {
            $client->last_name = '';
            $client->gender = 'unspecified';
            $client->registration_number = $request->registration_number;
            $client->registration_date = $request->registration_date;
            $client->kra_pin = $request->kra_pin;
            $client->nature_of_business = $request->nature_of_business;
            $client->business_category = $request->business_category;
            $client->membership_number = $request->membership_number;
            $client->date_of_admission = $request->date_of_admission;

            if ($request->has('witnesses')) {
                $client->witnesses = $request->witnesses;
            }
        } else {
            $client->last_name = $request->last_name;
            $client->gender = $request->gender;
            $client->dob = $request->dob;
            $client->id_number = $request->id_number;
            $client->passport_number = $request->passport_number;
            $client->employment_status = $request->employment_status;
            $client->employer = $request->employer_name;
            $client->workplace_address = $request->workplace_address;
            $client->employer_phone = $request->employer_phone;
            $client->occupation = $request->occupation;
            $client->marital_status = $request->marital_status;
            $client->title_id = $request->title_id;
            $client->profession_id = $request->profession_id;
            $client->country_id = $request->country_id;
            $client->loan_officer_id = $request->loan_officer_id;
        }

        if ($request->hasFile('photo')) {
            $file_name = $request->file('photo')->store('public/uploads/clients');
            $client->photo = basename($file_name);
        }
        $client->save();

        // Group/BMU Enrollment
        if ($request->sacco_group_id) {
            DB::table('sacco_group_members')->insert([
                'sacco_group_id' => $request->sacco_group_id,
                'client_id' => $client->id,
                'role' => 'member',
                'joined_date' => now(),
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        // Next of Kin Inline Save
        if (!$is_organisation && $request->has('next_of_kin')) {
            foreach ($request->next_of_kin as $nok) {
                if (!empty($nok['full_name'])) {
                    $names = explode(' ', trim($nok['full_name']), 2);
                    $first = $names[0];
                    $last = $names[1] ?? '';

                    $relId = null;
                    if (!empty($nok['relationship'])) {
                        $rel = \Modules\Client\Entities\ClientRelationship::firstOrCreate(['name' => ucfirst($nok['relationship'])]);
                        $relId = $rel->id;
                    }

                    DB::table('client_next_of_kin')->insert([
                        'client_id' => $client->id,
                        'created_by_id' => Auth::id(),
                        'first_name' => $first,
                        'last_name' => $last,
                        'id_number' => $nok['id_number'] ?? null,
                        'mobile' => $nok['phone_number'] ?? null,
                        'client_relationship_id' => $relId,
                        'gender' => 'unspecified',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        // Directors Inline Save
        if ($is_organisation && $request->has('directors')) {
            foreach ($request->directors as $index => $dir) {
                if (!empty($dir['full_name'])) {
                    $directorData = [
                        'client_id' => $client->id,
                        'full_name' => $dir['full_name'],
                        'id_number' => $dir['id_number'] ?? null,
                        'passport_number' => $dir['passport_number'] ?? null,
                        'kra_pin' => $dir['kra_pin'] ?? null,
                        'phone_number' => $dir['phone_number'] ?? null,
                        'position' => $dir['position'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    foreach (['photo', 'id_copy', 'pin_certificate'] as $fileKey) {
                        if ($request->hasFile("directors.{$index}.{$fileKey}")) {
                            $path = $request->file("directors.{$index}.{$fileKey}")->store('public/uploads/directors');
                            $directorData[$fileKey] = basename($path);
                        }
                    }

                    DB::table('client_directors')->insert($directorData);
                }
            }
        }

        if (function_exists('sacco_assign_member_number')) {
            sacco_assign_member_number($client);
        }
        custom_fields_save_form('add_client', $request, $client->id);
        activity()->on($client)
            ->withProperties(['id' => $client->id])
            ->log('Create Client');
        \flash(trans_choice("core::general.successfully_saved", 1))->success()->important();
        if ($request->input('_onboard')) {
            return redirect('client/' . $client->id . '/onboard');
        }
        return redirect('client');
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Response
     */
    public function show($id)
    {
        $client = Client::with('loan_officer')->find($id);
        if (!$client) {
            \flash(trans_choice('client::general.client', 1) . ' not found')->error()->important();
            return redirect('client');
        }
        $this->authorize('view', $client);
        $custom_fields = CustomField::where('category', 'add_client')->where('active', 1)->get();
        $member360 = class_exists(\Modules\Sacco\Services\Member360Service::class)
            ? (new \Modules\Sacco\Services\Member360Service())->getSummary($id)
            : null;
        $ussdConfig = class_exists(\Modules\Sacco\Entities\UssdMemberConfig::class)
            ? \Modules\Sacco\Entities\UssdMemberConfig::where('client_id', $id)->first()
            : null;
        $ussdSessions = class_exists(\Modules\Sacco\Entities\UssdSession::class)
            ? \Modules\Sacco\Entities\UssdSession::where('client_id', $id)->orderByDesc('id')->limit(5)->get()
            : collect();
        return theme_view('client::client.show', compact('client', 'custom_fields', 'member360', 'ussdConfig', 'ussdSessions'));
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Response
     */
    public function edit($id)
    {
        $client = Client::with(['next_of_kins', 'directors', 'client_type'])->find($id);
        if (!$client) {
            \flash(trans_choice('client::general.client', 1) . ' not found')->error()->important();
            return redirect('client');
        }
        $this->authorize('update', $client);
        $titles = Title::all();
        $professions = Profession::all();
        $client_types = ClientType::all();
        $users = User::whereHas('roles', function ($query) {
            return $query->where('name', '!=', 'client');
        })->get();
        $branches = Branch::all();
        $countries = Country::all();
        $custom_fields = CustomField::where('category', 'add_client')->where('active', 1)->get();
        $sacco_groups = class_exists(\Modules\Sacco\Entities\SaccoGroup::class) ? \Modules\Sacco\Entities\SaccoGroup::all() : collect();
        $sacco_group_id = DB::table('sacco_group_members')
            ->where('client_id', $client->id)
            ->where('status', 'active')
            ->value('sacco_group_id');
        return theme_view('client::client.edit', compact('client', 'titles', 'professions', 'client_types', 'users', 'branches', 'countries', 'custom_fields', 'sacco_groups', 'sacco_group_id'));
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function update(Request $request, $id)
    {
        $is_organisation = false;
        if ($request->client_type_id) {
            $client_type = ClientType::find($request->client_type_id);
            if ($client_type && strtolower($client_type->name) === 'organisation') {
                $is_organisation = true;
            }
        }

        if ($is_organisation) {
            $request->validate([
                'first_name' => ['required'], // Organisation Name
                'registration_number' => ['required'],
                'registration_date' => ['required', 'date'],
                'email' => ['nullable','email', 'max:255'],
            ]);
        } else {
            $request->validate([
                'first_name' => ['required'], // Full Name
                'last_name' => ['required'],
                'gender' => ['required'],
                'email' => ['nullable','email', 'max:255'],
                'dob' => ['required', 'date'],
                'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png'],
            ]);
        }

        $client = Client::find($id);
        if (!$client) {
            \flash(trans_choice('client::general.client', 1) . ' not found')->error()->important();
            return redirect('client');
        }
        $this->authorize('update', $client);

        $client->first_name = $request->first_name;
        $client->client_type_id = $request->client_type_id;
        $client->email = $request->email;
        $client->mobile = $request->mobile;
        $client->alternative_mobile = $request->alternative_mobile;
        $client->address = $request->address;
        $client->zip = $request->postal_code;
        $client->landing_site = $request->landing_site;
        $client->years_in_business = $request->years_in_business;
        $client->notes = $request->notes;

        if ($is_organisation) {
            $client->last_name = '';
            $client->gender = 'unspecified';
            $client->registration_number = $request->registration_number;
            $client->registration_date = $request->registration_date;
            $client->kra_pin = $request->kra_pin;
            $client->nature_of_business = $request->nature_of_business;
            $client->business_category = $request->business_category;
            $client->membership_number = $request->membership_number;
            $client->date_of_admission = $request->date_of_admission;

            if ($request->has('witnesses')) {
                $client->witnesses = $request->witnesses;
            }
        } else {
            $client->last_name = $request->last_name;
            $client->gender = $request->gender;
            $client->dob = $request->dob;
            $client->id_number = $request->id_number;
            $client->passport_number = $request->passport_number;
            $client->employment_status = $request->employment_status;
            $client->employer = $request->employer_name;
            $client->workplace_address = $request->workplace_address;
            $client->employer_phone = $request->employer_phone;
            $client->occupation = $request->occupation;
            $client->marital_status = $request->marital_status;
            $client->title_id = $request->title_id;
            $client->profession_id = $request->profession_id;
            $client->country_id = $request->country_id;
            $client->loan_officer_id = $request->loan_officer_id;
        }

        if ($request->hasFile('photo')) {
            $file_name = $request->file('photo')->store('public/uploads/clients');
            if ($client->photo) {
                Storage::delete('public/uploads/clients/' . $client->photo);
            }
            $client->photo = basename($file_name);
        }
        $client->save();

        // BMU / Sacco Group Sync
        if ($request->has('sacco_group_id')) {
            DB::table('sacco_group_members')->where('client_id', $client->id)->delete();
            if ($request->sacco_group_id) {
                DB::table('sacco_group_members')->insert([
                    'sacco_group_id' => $request->sacco_group_id,
                    'client_id' => $client->id,
                    'role' => 'member',
                    'joined_date' => now(),
                    'status' => 'active',
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        }

        // Next of Kin Sync
        if (!$is_organisation && $request->has('next_of_kin')) {
            DB::table('client_next_of_kin')->where('client_id', $client->id)->delete();
            foreach ($request->next_of_kin as $nok) {
                if (!empty($nok['full_name'])) {
                    $names = explode(' ', trim($nok['full_name']), 2);
                    $first = $names[0];
                    $last = $names[1] ?? '';

                    $relId = null;
                    if (!empty($nok['relationship'])) {
                        $rel = \Modules\Client\Entities\ClientRelationship::firstOrCreate(['name' => ucfirst($nok['relationship'])]);
                        $relId = $rel->id;
                    }

                    DB::table('client_next_of_kin')->insert([
                        'client_id' => $client->id,
                        'created_by_id' => Auth::id(),
                        'first_name' => $first,
                        'last_name' => $last,
                        'id_number' => $nok['id_number'] ?? null,
                        'mobile' => $nok['phone_number'] ?? null,
                        'client_relationship_id' => $relId,
                        'gender' => 'unspecified',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        // Directors Sync
        if ($is_organisation && $request->has('directors')) {
            // Delete old director files
            $oldDirs = DB::table('client_directors')->where('client_id', $client->id)->get();
            foreach ($oldDirs as $od) {
                foreach (['photo', 'id_copy', 'pin_certificate'] as $f) {
                    // Check if new file is replacing it, or if it's completely deleted.
                    // For safety, we only delete if the director is removed or if new file is uploaded.
                }
            }

            // Create temporary map of old files
            $oldFiles = [];
            foreach ($oldDirs as $od) {
                $oldFiles[$od->id] = [
                    'photo' => $od->photo,
                    'id_copy' => $od->id_copy,
                    'pin_certificate' => $od->pin_certificate,
                ];
            }

            DB::table('client_directors')->where('client_id', $client->id)->delete();

            foreach ($request->directors as $index => $dir) {
                if (!empty($dir['full_name'])) {
                    $directorData = [
                        'client_id' => $client->id,
                        'full_name' => $dir['full_name'],
                        'id_number' => $dir['id_number'] ?? null,
                        'passport_number' => $dir['passport_number'] ?? null,
                        'kra_pin' => $dir['kra_pin'] ?? null,
                        'phone_number' => $dir['phone_number'] ?? null,
                        'position' => $dir['position'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    $oldDirId = $dir['id'] ?? null;

                    foreach (['photo', 'id_copy', 'pin_certificate'] as $fileKey) {
                        if ($request->hasFile("directors.{$index}.{$fileKey}")) {
                            // Delete old file
                            if ($oldDirId && isset($oldFiles[$oldDirId][$fileKey]) && $oldFiles[$oldDirId][$fileKey]) {
                                Storage::delete('public/uploads/directors/' . $oldFiles[$oldDirId][$fileKey]);
                            }
                            $path = $request->file("directors.{$index}.{$fileKey}")->store('public/uploads/directors');
                            $directorData[$fileKey] = basename($path);
                        } else {
                            $directorData[$fileKey] = ($oldDirId && isset($oldFiles[$oldDirId][$fileKey])) ? $oldFiles[$oldDirId][$fileKey] : null;
                        }
                    }

                    DB::table('client_directors')->insert($directorData);
                }
            }
        }

        custom_fields_save_form('add_client', $request, $client->id);
        activity()->on($client)
            ->withProperties(['id' => $client->id])
            ->log('Update Client');
        \flash(trans_choice("core::general.successfully_saved", 1))->success()->important();
        return redirect('client');
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Response
     */
    public function destroy($id)
    {
        $client = Client::find($id);
        if (!$client) {
            \flash(trans_choice('client::general.client', 1) . ' not found')->error()->important();
            return redirect('client');
        }
        $this->authorize('delete', $client);
        $client->delete();
        activity()->on($client)
            ->withProperties(['id' => $client->id])
            ->log('Delete Client');
        \flash(trans_choice("core::general.successfully_deleted", 1))->success()->important();
        return redirect()->back();
    }

    public function onboard($id)
    {
        $client = Client::with(['identifications', 'next_of_kins', 'client_users'])->find($id);
        if (!$client) {
            \flash(trans_choice('client::general.client', 1) . ' not found')->error()->important();
            return redirect('client');
        }
        $this->authorize('view', $client);

        $hasIdentification = $client->identifications->isNotEmpty();
        $hasNextOfKin = $client->next_of_kins->isNotEmpty();
        $hasPortalAccess = $client->client_users->isNotEmpty();

        $stepsCompleted = 1 // basic info always done
            + ($hasIdentification ? 1 : 0)
            + ($hasNextOfKin ? 1 : 0)
            + ($hasPortalAccess ? 1 : 0);
        $totalSteps = 4;

        $progress = [
            'completed' => $stepsCompleted,
            'total' => $totalSteps,
            'percent' => (int) round(($stepsCompleted / $totalSteps) * 100),
        ];

        return theme_view('client::client.onboarding', compact(
            'client', 'hasIdentification', 'hasNextOfKin', 'hasPortalAccess', 'progress'
        ));
    }

    public function create_user($id)
    {
        $users = User::role('client')->get();
        $client = Client::find($id);
        return theme_view('client::client.create_user', compact('users', 'client'));
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Response
     */
    public function store_user(Request $request, $id)
    {
        if ($request->existing == 1) {
            $request->validate([
                'user_id' => ['required'],
            ]);
            if (ClientUser::where('client_id', $id)->where('user_id', $request->user_id)->get()->count() > 0) {
                \flash(trans_choice("client::general.user_already_added", 1))->error()->important();
                return redirect()->back();
            }
            $client_user = new ClientUser();
            $client_user->client_id = $id;
            $client_user->created_by_id = Auth::id();
            $client_user->user_id = $request->user_id;
            $client_user->save();
        } else {
            $request->validate([
                'first_name' => ['required'],
                'last_name' => ['required'],
                'gender' => ['required'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
                'password' => ['required', 'string', 'min:6', 'confirmed'],
            ]);
            $user = User::create([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'gender' => $request->gender,
                'phone' => $request->phone,
                'email' => $request->email,
                'notes' => $request->notes,
                'address' => $request->address,
                'password' => Hash::make($request->password),
                'email_verified_at' => date("Y-m-d H:i:s")
            ]);
            //attach client role
            $role = Role::findByName('client');
            $user->assignRole($role);
            $client_user = new ClientUser();
            $client_user->client_id = $id;
            $client_user->created_by_id = Auth::id();
            $client_user->user_id = $user->id;
            $client_user->save();
        }
        activity()->log('Create Client User');
        \flash(trans_choice("core::general.successfully_saved", 1))->success()->important();
        return redirect('client/' . $id . '/show');
    }

    public function destroy_user($id)
    {
        ClientUser::destroy($id);
        activity()->log('Delete Client User');
        \flash(trans_choice("core::general.successfully_deleted", 1))->success()->important();
        return redirect()->back();
    }

    public function change_status(Request $request, $id)
    {
        $request->validate([
            'status' => ['required'],
            'date' => ['required', 'date'],
            'member_status_detail' => ['nullable', 'in:active,dormant,defaulting,exit,pending,inactive,closed,deceased'],
            'kyc_verified' => ['nullable', 'in:0,1'],
        ]);
        $client = Client::find($id);
        // Activation requires a second-level approval (maker-checker)
        if ($request->status === 'active' && $client->status !== 'active') {
            if (function_exists('sacco_requires_maker_checker_for') && sacco_requires_maker_checker_for(0, 'client_activation')) {
                if (function_exists('sacco_queue_approval')) {
                    sacco_queue_approval(
                        'client_activation',
                        $client->id,
                        0,
                        'Client activation pending checker approval',
                        'Client Activation — ' . $client->first_name . ' ' . $client->last_name,
                        [
                            'status'               => $request->status,
                            'member_status_detail' => $request->member_status_detail,
                            'kyc_verified'         => $request->kyc_verified,
                        ]
                    );
                    activity()->on($client)->withProperties(['id' => $client->id])->log('Queue Client Activation');
                    \flash('Client activation submitted for checker review.')->warning()->important();
                    return redirect()->back();
                }
            }
        }
        $client->status = $request->status;
        if ($request->has('member_status_detail') && $request->member_status_detail !== '') {
            $client->member_status_detail = $request->member_status_detail;
        }
        if ($request->has('kyc_verified')) {
            $client->kyc_verified = (int)$request->kyc_verified;
        }
        $client->save();
        activity()->on($client)
            ->withProperties(['id' => $client->id])
            ->log('Update Client Status');
        \flash(trans_choice("core::general.successfully_saved", 1))->success()->important();
        return redirect()->back();
    }

}
