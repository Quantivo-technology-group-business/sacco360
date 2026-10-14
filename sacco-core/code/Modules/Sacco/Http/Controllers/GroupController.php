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
        $this->middleware('permission:sacco.groups.create')->only(['create', 'store', 'addMember', 'postContributions', 'generateSchedule']);
        $this->middleware('permission:sacco.groups.manage')->only(['removeMember', 'updateMemberRole', 'updateSettings']);
        $this->middleware('permission:sacco.groups.contributions')->only(['contributions', 'get_contributions']);
        $this->middleware('permission:sacco.groups.approve')->only(['approveContribution', 'rejectContribution', 'reverseContribution']);
        $this->middleware('permission:sacco.groups.bidding')->only(['swapTurns', 'skipTurn', 'submitBid', 'resolveBidCycle']);
        $this->middleware('permission:sacco.groups.disburse')->only(['disburseCycle']);
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

        // Surfaces the approval queue on the group landing page; without it the
        // contribution register had no entry point in the UI.
        $pendingCount = SaccoGroupContribution::where('sacco_group_id', $id)
            ->where('status', 'pending')
            ->count();

        return theme_view('sacco::group.show', compact('group', 'clients', 'pendingCount'));
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
        $group = SaccoGroup::findOrFail($id);

        $pendingCount = SaccoGroupContribution::where('sacco_group_id', $id)
            ->where('status', 'pending')
            ->count();

        return theme_view('sacco::group.contributions', compact('group', 'pendingCount'));
    }

    /**
     * Server-side DataTable for the group contribution register.
     *
     * Replaces the old read-only paginate(30) table, which gave officers no way
     * to search a period or act on a pending contribution.
     */
    public function get_contributions(Request $request, $id)
    {
        $group = SaccoGroup::findOrFail($id);

        $query = SaccoGroupContribution::with(['client', 'approvedBy'])
            ->where('sacco_group_id', $group->id);

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }
        if ($period = $request->input('period')) {
            $query->where('period', $period);
        }

        return DataTables::of($query)
            ->addColumn('member', fn($c) => $c->client->full_name ?? '—')
            ->addColumn('amount_formatted', fn($c) => number_format((float) $c->amount, 2))
            ->addColumn('method', fn($c) => ucfirst($c->payment_method ?? '—'))
            ->addColumn('reference', fn($c) => $c->mpesa_receipt_number ?: ($c->payment_reference ?: '—'))
            ->addColumn('status_badge', function ($c) {
                $map = [
                    'posted'   => 'success',
                    'pending'  => 'warning',
                    'rejected' => 'danger',
                    'reversed' => 'secondary',
                ];
                $class = $map[$c->status] ?? 'secondary';

                return '<span class="badge badge-' . $class . '">' . ucfirst($c->status) . '</span>';
            })
            ->addColumn('action', function ($c) use ($group) {
                $base = url('sacco/group/' . $group->id);
                $html = '';

                if ($c->status === 'pending' && auth()->user()->can('sacco.groups.approve')) {
                    $html .= '<form method="POST" action="' . $base . '/contribution/' . $c->id . '/approve" class="d-inline">'
                           . csrf_field()
                           . '<button type="submit" class="btn btn-sm btn-success" title="Approve"><i class="fas fa-check"></i></button>'
                           . '</form> ';
                    $html .= '<button type="button" class="btn btn-sm btn-danger reject-btn" data-id="' . $c->id . '" title="Reject"><i class="fas fa-times"></i></button>';
                } elseif ($c->status === 'posted' && auth()->user()->can('sacco.groups.approve')) {
                    $html .= '<button type="button" class="btn btn-sm btn-warning reverse-btn" data-id="' . $c->id . '" title="Reverse"><i class="fas fa-undo"></i></button>';
                }

                if ($c->rejection_reason) {
                    $html .= ' <i class="fas fa-info-circle" title="' . e($c->rejection_reason) . '"></i>';
                }

                return $html ?: '—';
            })
            ->rawColumns(['status_badge', 'action'])
            ->make(true);
    }

    /**
     * Confirm receipt of a member-submitted contribution.
     */
    public function approveContribution($id, $contributionId, GroupService $service)
    {
        $contribution = SaccoGroupContribution::where('sacco_group_id', $id)->findOrFail($contributionId);

        try {
            $service->approveContribution($contribution, Auth::id());
            \flash('Contribution of ' . number_format((float) $contribution->amount, 2) . ' approved.')->success()->important();
        } catch (\Exception $e) {
            \flash($e->getMessage())->error()->important();
        }

        return redirect('sacco/group/' . $id . '/contributions');
    }

    /**
     * Reject a pending contribution, recording why.
     */
    public function rejectContribution(Request $request, $id, $contributionId, GroupService $service)
    {
        $request->validate(['reason' => 'required|string|max:255']);

        $contribution = SaccoGroupContribution::where('sacco_group_id', $id)->findOrFail($contributionId);

        try {
            $service->rejectContribution($contribution, $request->reason, Auth::id());
            \flash('Contribution rejected.')->warning()->important();
        } catch (\Exception $e) {
            \flash($e->getMessage())->error()->important();
        }

        return redirect('sacco/group/' . $id . '/contributions');
    }

    /**
     * Reverse an already-posted contribution (duplicate, wrong amount, bounce).
     */
    public function reverseContribution(Request $request, $id, $contributionId, GroupService $service)
    {
        $request->validate(['reason' => 'required|string|max:255']);

        $contribution = SaccoGroupContribution::where('sacco_group_id', $id)->findOrFail($contributionId);

        try {
            $service->reverseContribution($contribution, $request->reason, Auth::id());
            \flash('Contribution reversed.')->warning()->important();
        } catch (\Exception $e) {
            \flash($e->getMessage())->error()->important();
        }

        return redirect('sacco/group/' . $id . '/contributions');
    }

    /**
     * Change an officer/member role within the group.
     *
     * Previously declared in the middleware list but never implemented, so role
     * changes were both unguarded and impossible.
     */
    public function updateMemberRole(Request $request, $id, $memberId)
    {
        $request->validate([
            'role' => 'required|in:member,chairperson,secretary,treasurer,committee',
        ]);

        $group  = SaccoGroup::findOrFail($id);
        $member = SaccoGroupMember::where('sacco_group_id', $id)->findOrFail($memberId);

        $member->role = $request->role;
        $member->save();

        // Keep the denormalised officer pointers on the group in step, so the
        // portal and reports do not disagree with the membership row.
        $officerColumn = [
            'chairperson' => 'chairperson_client_id',
            'secretary'   => 'secretary_client_id',
            'treasurer'   => 'treasurer_client_id',
        ][$request->role] ?? null;

        if ($officerColumn) {
            $group->{$officerColumn} = $member->client_id;
            $group->save();
        }

        \flash('Member role updated to ' . ucfirst($request->role) . '.')->success()->important();

        return redirect('sacco/group/' . $id . '/show');
    }
}
