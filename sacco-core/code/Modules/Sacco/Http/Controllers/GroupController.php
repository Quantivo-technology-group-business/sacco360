<?php

namespace Modules\Sacco\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Sacco\Entities\SaccoGroup;
use Modules\Sacco\Entities\SaccoGroupMember;
use Modules\Sacco\Entities\SaccoGroupContribution;
use Modules\Sacco\Entities\SaccoMerryGoRoundCycle;
use Modules\Sacco\Services\GroupService;
use Modules\Client\Entities\Client;
use Modules\Branch\Entities\Branch;
use Yajra\DataTables\Facades\DataTables;

class GroupController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', '2fa']);
        $this->middleware('permission:sacco.groups.index')->only(['index', 'show', 'get_groups']);
        $this->middleware('permission:sacco.groups.create')->only(['create', 'store', 'addMember', 'storeMember', 'postContributions', 'generateSchedule']);
        $this->middleware('permission:sacco.groups.manage')->only(['removeMember', 'updateMemberRole']);
    }

    public function index()
    {
        return theme_view('sacco::group.index');
    }

    public function get_groups(Request $request)
    {
        $query = SaccoGroup::withCount('members')
            ->with('branch')
            ->orderByDesc('id');

        return DataTables::of($query)
            ->addColumn('branch_name', fn($g) => $g->branch->name ?? '—')
            ->addColumn('type_label', fn($g) => ucfirst(str_replace('_', ' ', $g->type)))
            ->addColumn('status_badge', fn($g) =>
                '<span class="badge badge-' . ($g->status === 'active' ? 'success' : ($g->status === 'suspended' ? 'warning' : 'danger')) . '">' . ucfirst($g->status) . '</span>'
            )
            ->addColumn('action', function ($g) {
                return '<a href="' . url('sacco/group/' . $g->id . '/show') . '" class="btn btn-sm btn-info"><i class="fas fa-eye"></i> View</a>';
            })
            ->rawColumns(['status_badge', 'action'])
            ->make(true);
    }

    public function create()
    {
        $branches = Branch::where('active', 1)->orderBy('name')->get();
        $clients  = Client::where('status', 1)->orderBy('first_name')->get();
        return theme_view('sacco::group.create', compact('branches', 'clients'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'                 => 'required|string|max:150',
            'type'                 => 'required|in:chama,merry_go_round,table_banking,investment_club,other',
            'monthly_contribution' => 'nullable|numeric|min:0',
            'rotation_model'       => 'nullable|in:fixed,random,bidding',
            'late_payment_penalty' => 'nullable|numeric|min:0',
        ]);

        SaccoGroup::create(array_merge(
            $request->only([
                'name', 'code', 'type', 'branch_id',
                'registration_number', 'registration_date',
                'max_members', 'share_value', 'monthly_contribution',
                'purpose', 'chairperson_client_id',
                'secretary_client_id', 'treasurer_client_id',
                'rotation_model', 'late_payment_penalty',
            ]),
            ['created_by_id' => Auth::id(), 'status' => 'active']
        ));

        \flash(trans_choice('core::general.successfully_saved', 1))->success()->important();
        return redirect('sacco/group');
    }

    public function show($id)
    {
        $group   = SaccoGroup::with(['members.client', 'merryGoRoundCycles.beneficiary'])->findOrFail($id);
        $clients = Client::where('status', 1)->orderBy('first_name')->get();
        return theme_view('sacco::group.show', compact('group', 'clients'));
    }

    public function addMember(Request $request, $id, GroupService $service)
    {
        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'role'      => 'required|in:member,chairperson,secretary,treasurer,committee',
        ]);

        $group = SaccoGroup::findOrFail($id);

        try {
            $service->addMember($group, (int) $request->client_id, $request->role, Auth::id());
            \flash('Member added to group.')->success()->important();
        } catch (\Exception $e) {
            \flash($e->getMessage())->error()->important();
        }

        return redirect('sacco/group/' . $id . '/show');
    }

    public function removeMember($id, $memberId, GroupService $service)
    {
        $member = SaccoGroupMember::where('sacco_group_id', $id)->findOrFail($memberId);
        $service->removeMember($member, null, Auth::id());
        \flash('Member exited from group.')->warning()->important();
        return redirect('sacco/group/' . $id . '/show');
    }

    public function postContributions(Request $request, $id, GroupService $service)
    {
        $request->validate([
            'period' => 'required|string|regex:/^\d{4}-\d{2}$/',
        ]);

        $group  = SaccoGroup::findOrFail($id);
        $posted = $service->postContributions($group, $request->period, Auth::id());

        \flash("{$posted} contributions posted for period {$request->period}.")->success()->important();
        return redirect('sacco/group/' . $id . '/show');
    }

    public function generateSchedule(Request $request, $id, GroupService $service)
    {
        $request->validate(['start_date' => 'required|date']);
        $group = SaccoGroup::findOrFail($id);

        try {
            $service->generateMerryGoRoundSchedule($group, Carbon::parse($request->start_date), Auth::id());
            \flash('Merry-go-round schedule generated.')->success()->important();
        } catch (\Exception $e) {
            \flash($e->getMessage())->error()->important();
        }

        return redirect('sacco/group/' . $id . '/show');
    }

    public function updateSettings(Request $request, $id)
    {
        $request->validate([
            'rotation_model'       => 'required|in:fixed,random,bidding',
            'late_payment_penalty' => 'nullable|numeric|min:0',
        ]);

        $group = SaccoGroup::findOrFail($id);
        $group->fill($request->only(['rotation_model', 'late_payment_penalty']));
        $group->save();

        \flash('Group rotation settings updated.')->success()->important();
        return redirect('sacco/group/' . $id . '/show');
    }

    public function swapTurns(Request $request, $id, GroupService $service)
    {
        $request->validate([
            'cycle_id_a' => 'required|exists:sacco_merry_go_round_cycles,id',
            'cycle_id_b' => 'required|exists:sacco_merry_go_round_cycles,id|different:cycle_id_a',
        ]);

        try {
            $service->swapMemberTurns((int) $request->cycle_id_a, (int) $request->cycle_id_b);
            \flash('Turns swapped successfully.')->success()->important();
        } catch (\Exception $e) {
            \flash($e->getMessage())->error()->important();
        }

        return redirect('sacco/group/' . $id . '/show');
    }

    public function skipTurn($id, $cycleId, GroupService $service)
    {
        $cycle = SaccoMerryGoRoundCycle::where('sacco_group_id', $id)->findOrFail($cycleId);

        try {
            $service->skipMemberTurn($cycle, Auth::id());
            \flash('Turn skipped successfully.')->success()->important();
        } catch (\Exception $e) {
            \flash($e->getMessage())->error()->important();
        }

        return redirect('sacco/group/' . $id . '/show');
    }

    public function submitBid(Request $request, $id, $cycleId, GroupService $service)
    {
        $request->validate([
            'client_id'  => 'required|exists:clients,id',
            'bid_amount' => 'required|numeric|min:0',
        ]);

        $cycle = SaccoMerryGoRoundCycle::where('sacco_group_id', $id)->findOrFail($cycleId);

        try {
            $service->submitBid($id, $cycle->cycle_number, (int) $request->client_id, (float) $request->bid_amount);
            \flash('Bid submitted successfully.')->success()->important();
        } catch (\Exception $e) {
            \flash($e->getMessage())->error()->important();
        }

        return redirect('sacco/group/' . $id . '/show');
    }

    public function resolveBidCycle($id, $cycleId, GroupService $service)
    {
        $cycle = SaccoMerryGoRoundCycle::where('sacco_group_id', $id)->findOrFail($cycleId);

        try {
            $service->resolveBiddingCycle($cycle->id);
            \flash('Bidding cycle resolved successfully.')->success()->important();
        } catch (\Exception $e) {
            \flash($e->getMessage())->error()->important();
        }

        return redirect('sacco/group/' . $id . '/show');
    }

    public function disburseCycle(Request $request, $id, $cycleId, GroupService $service)
    {
        $request->validate(['reference' => 'nullable|string|max:100']);
        $cycle = SaccoMerryGoRoundCycle::where('sacco_group_id', $id)->findOrFail($cycleId);

        try {
            $service->disburseCycle($cycle->id, $request->reference ?: 'AUTO-' . strtoupper(uniqid()));
            \flash('Cycle disbursed successfully.')->success()->important();
        } catch (\Exception $e) {
            \flash($e->getMessage())->error()->important();
        }

        return redirect('sacco/group/' . $id . '/show');
    }

    public function contributions($id)
    {
        $group         = SaccoGroup::findOrFail($id);
        $contributions = SaccoGroupContribution::with('client')
            ->where('sacco_group_id', $id)
            ->orderByDesc('contribution_date')
            ->paginate(30);
        return theme_view('sacco::group.contributions', compact('group', 'contributions'));
    }
}
