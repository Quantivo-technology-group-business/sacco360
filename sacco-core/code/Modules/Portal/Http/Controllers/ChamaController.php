<?php

namespace Modules\Portal\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Sacco\Entities\SaccoGroup;
use Modules\Sacco\Entities\SaccoGroupMember;
use Modules\Sacco\Entities\SaccoGroupContribution;
use Modules\Sacco\Entities\SaccoMeeting;
use Modules\Client\Entities\Client;
use Modules\Client\Entities\ClientUser;

class ChamaController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'portal.permission']);
    }

    // ── Groups ────────────────────────────────────────────────────────────────

    /**
     * List all groups the logged-in member belongs to.
     */
    public function index()
    {
        $client = $this->requireClient();
        if ($client instanceof \Illuminate\Http\RedirectResponse) return $client;

        $memberships = SaccoGroupMember::with(['group.branch'])
            ->where('client_id', $client->id)
            ->where('status', 'active')
            ->get();

        return theme_view('portal::chama.index', compact('memberships', 'client'));
    }

    /**
     * Show a specific group's details for the member.
     */
    public function show($id)
    {
        $client = $this->requireClient();
        if ($client instanceof \Illuminate\Http\RedirectResponse) return $client;

        $membership = SaccoGroupMember::where('sacco_group_id', $id)
            ->where('client_id', $client->id)
            ->where('status', 'active')
            ->firstOrFail();

        $group = SaccoGroup::with([
            'members.client',
            'merryGoRoundCycles.beneficiary',
            'branch',
            'chairperson',
            'secretary',
            'treasurer',
        ])->findOrFail($id);

        $contributions = SaccoGroupContribution::where('sacco_group_id', $id)
            ->where('client_id', $client->id)
            ->orderByDesc('contribution_date')
            ->get();

        return theme_view('portal::chama.show', compact('group', 'membership', 'contributions', 'client'));
    }

    // ── Contributions ─────────────────────────────────────────────────────────

    /**
     * Show contribution form for a group.
     */
    public function contribute($id)
    {
        $client = $this->requireClient();
        if ($client instanceof \Illuminate\Http\RedirectResponse) return $client;

        $membership = SaccoGroupMember::where('sacco_group_id', $id)
            ->where('client_id', $client->id)
            ->where('status', 'active')
            ->firstOrFail();

        $group = SaccoGroup::findOrFail($id);

        // Check if already contributed this period
        $currentPeriod = date('Y-m');
        $alreadyPaid = SaccoGroupContribution::where('sacco_group_id', $id)
            ->where('client_id', $client->id)
            ->where('period', $currentPeriod)
            ->whereIn('status', ['posted', 'pending'])
            ->exists();

        // FOSA balance for "pay from savings" option
        $fosaBalance = 0;
        $fosaAccount = null;
        if (class_exists(\Modules\Sacco\Services\WingService::class)) {
            try {
                $wing = new \Modules\Sacco\Services\WingService();
                $fosaAccount = $wing->getFosaAccount($client->id);
                if ($fosaAccount) {
                    $fosaBalance = $wing->getAccountBalance($fosaAccount->id);
                }
            } catch (\Throwable $e) {
                // SACCO wing service unavailable — silently skip
            }
        }

        $mpesaEnabled = class_exists(\Modules\Sacco\Services\MpesaStkService::class);

        return theme_view('portal::chama.contribute', compact(
            'group', 'membership', 'client',
            'currentPeriod', 'alreadyPaid',
            'fosaBalance', 'fosaAccount', 'mpesaEnabled'
        ));
    }

    /**
     * Process a member's contribution.
     */
    public function storeContribution(Request $request, $id)
    {
        $client = $this->requireClient();
        if ($client instanceof \Illuminate\Http\RedirectResponse) return $client;

        $membership = SaccoGroupMember::where('sacco_group_id', $id)
            ->where('client_id', $client->id)
            ->where('status', 'active')
            ->firstOrFail();

        $group = SaccoGroup::findOrFail($id);

        $request->validate([
            'period'         => 'required|string|regex:/^\d{4}-\d{2}$/',
            'amount'         => 'required|numeric|min:1',
            'payment_method' => 'required|in:cash,mpesa,savings,bank,other',
            'phone_number'   => 'nullable|string|max:20',
            'notes'          => 'nullable|string|max:500',
        ]);

        $period = $request->period;
        $amount = (float) $request->amount;
        $method = $request->payment_method;

        // Prevent double payment for the same period
        $exists = SaccoGroupContribution::where('sacco_group_id', $id)
            ->where('client_id', $client->id)
            ->where('period', $period)
            ->whereIn('status', ['posted', 'pending'])
            ->exists();

        if ($exists) {
            \flash("You have already recorded a contribution for {$period}.")->warning()->important();
            return redirect()->back();
        }

        // M-Pesa STK push path
        if ($method === 'mpesa' && class_exists(\Modules\Sacco\Services\MpesaStkService::class)) {
            $phone = $request->phone_number
                ?: ($client->mobile ?? $client->phone ?? null);

            if (!$phone) {
                \flash('Please provide a phone number for M-Pesa payment.')->error()->important();
                return redirect()->back()->withInput();
            }

            try {
                // Record as pending first, then STK push
                $contribution = SaccoGroupContribution::create([
                    'sacco_group_id'        => $group->id,
                    'sacco_group_member_id' => $membership->id,
                    'client_id'             => $client->id,
                    'amount'                => $amount,
                    'contribution_date'     => Carbon::today()->toDateString(),
                    'period'                => $period,
                    'created_by_id'         => Auth::id(),
                    'status'                => 'pending',
                    'payment_method'        => 'mpesa',
                    'phone_number'          => $phone,
                    'notes'                 => $request->notes,
                ]);

                (new \Modules\Sacco\Services\MpesaStkService())->pushGroupContribution(
                    $client->id,
                    $group->id,
                    $contribution->id,
                    $amount,
                    $phone
                );

                \flash("M-Pesa STK push sent to {$phone}. Your contribution of KES " . number_format($amount, 2) . " will be confirmed once payment is received.")->success()->important();
            } catch (\Throwable $e) {
                // STK push failed — keep contribution as pending with note
                \flash("Contribution recorded as pending. STK push failed: " . $e->getMessage())->warning()->important();
            }

            return redirect('portal/sacco/chama/' . $id . '/show');
        }

        // Pay from FOSA savings
        if ($method === 'savings') {
            if (!class_exists(\Modules\Sacco\Services\WingService::class)) {
                \flash('Savings debit is not available.')->error()->important();
                return redirect()->back();
            }

            try {
                $wing = new \Modules\Sacco\Services\WingService();
                $fosa = $wing->getFosaAccount($client->id);

                if (!$fosa) {
                    \flash('No FOSA savings account found.')->error()->important();
                    return redirect()->back();
                }

                $balance = $wing->getAccountBalance($fosa->id);
                if ($balance < $amount) {
                    \flash('Insufficient FOSA balance. Available: KES ' . number_format($balance, 2))->error()->important();
                    return redirect()->back();
                }

                // Debit FOSA via SavingsChannelService or direct transaction
                if (class_exists(\Modules\Sacco\Services\SavingsChannelService::class)) {
                    $ref = 'CHAMA-' . $group->id . '-' . strtoupper(uniqid());
                    (new \Modules\Sacco\Services\SavingsChannelService())->postWithdrawal(
                        $fosa, $amount, 'portal_chama', $ref
                    );
                } else {
                    // Fallback: direct savings withdrawal
                    \Modules\Savings\Entities\SavingsTransaction::create([
                        'savings_id'                  => $fosa->id,
                        'branch_id'                   => $fosa->branch_id,
                        'created_by_id'               => Auth::id(),
                        'name'                        => 'CHAMA Contribution — ' . $group->name . ' (' . $period . ')',
                        'savings_transaction_type_id' => 2, // withdrawal
                        'submitted_on'                => date('Y-m-d'),
                        'created_on'                  => date('Y-m-d'),
                        'amount'                      => $amount,
                        'reversible'                  => 0,
                    ]);
                }

                SaccoGroupContribution::create([
                    'sacco_group_id'        => $group->id,
                    'sacco_group_member_id' => $membership->id,
                    'client_id'             => $client->id,
                    'amount'                => $amount,
                    'contribution_date'     => Carbon::today()->toDateString(),
                    'period'                => $period,
                    'created_by_id'         => Auth::id(),
                    'status'                => 'posted',
                    'payment_method'        => 'savings',
                    'notes'                 => 'Debited from FOSA. ' . ($request->notes ?? ''),
                ]);

                \flash('Contribution of KES ' . number_format($amount, 2) . ' debited from your FOSA savings.')->success()->important();
            } catch (\Throwable $e) {
                \flash('Could not debit savings: ' . $e->getMessage())->error()->important();
                return redirect()->back();
            }

            return redirect('portal/sacco/chama/' . $id . '/show');
        }

        // Cash / bank / other — record as pending (admin confirms receipt)
        SaccoGroupContribution::create([
            'sacco_group_id'        => $group->id,
            'sacco_group_member_id' => $membership->id,
            'client_id'             => $client->id,
            'amount'                => $amount,
            'contribution_date'     => Carbon::today()->toDateString(),
            'period'                => $period,
            'created_by_id'         => Auth::id(),
            'status'                => 'pending',
            'payment_method'        => $method,
            'payment_reference'     => $request->payment_reference ?? null,
            'notes'                 => $request->notes,
        ]);

        \flash('Contribution of KES ' . number_format($amount, 2) . ' recorded as pending. The admin will confirm receipt.')->info()->important();

        return redirect('portal/sacco/chama/' . $id . '/show');
    }

    // ── Rotation ──────────────────────────────────────────────────────────────

    /**
     * Show the member's rotation schedule across all their merry-go-round groups.
     */
    public function myRotation()
    {
        $client = $this->requireClient();
        if ($client instanceof \Illuminate\Http\RedirectResponse) return $client;

        // Get all active MGR memberships
        $memberships = SaccoGroupMember::with([
                'group.merryGoRoundCycles.beneficiary',
                'group.members.client',
            ])
            ->where('client_id', $client->id)
            ->where('status', 'active')
            ->get()
            ->filter(fn($m) => $m->group && $m->group->type === 'merry_go_round');

        // Build rotation data per group
        $rotations = $memberships->map(function ($membership) use ($client) {
            $group  = $membership->group;
            $cycles = $group->merryGoRoundCycles->sortBy('cycle_number');

            $myUpcomingCycle = $cycles
                ->whereIn('status', ['upcoming', 'active'])
                ->where('beneficiary_client_id', $client->id)
                ->first();

            $myPastCycles = $cycles
                ->whereIn('status', ['completed', 'paid'])
                ->where('beneficiary_client_id', $client->id)
                ->values();

            $nextCycle = $cycles->whereIn('status', ['upcoming', 'active'])->first();

            $daysUntilMyTurn = null;
            if ($myUpcomingCycle && $myUpcomingCycle->cycle_date) {
                $daysUntilMyTurn = (int) Carbon::today()->diffInDays(
                    Carbon::parse($myUpcomingCycle->cycle_date),
                    false
                );
            }

            return [
                'group'            => $group,
                'membership'       => $membership,
                'all_cycles'       => $cycles,
                'my_upcoming'      => $myUpcomingCycle,
                'my_past'          => $myPastCycles,
                'next_cycle'       => $nextCycle,
                'days_until'       => $daysUntilMyTurn,
                'total_members'    => $group->members->where('status', 'active')->count(),
                'my_position'      => $myUpcomingCycle?->cycle_number,
            ];
        })->values();

        return theme_view('portal::chama.my_rotation', compact('rotations', 'client'));
    }

    // ── Meetings ──────────────────────────────────────────────────────────────

    public function meetings()
    {
        $client = $this->getClient();

        $meetings = SaccoMeeting::withCount(['agendaItems', 'resolutions', 'attendances'])
            ->orderByDesc('scheduled_date')
            ->paginate(20);

        return theme_view('portal::chama.meetings', compact('meetings', 'client'));
    }

    public function showMeeting($id)
    {
        $client  = $this->getClient();
        $meeting = SaccoMeeting::with(['agendaItems', 'resolutions', 'attendances.client'])->findOrFail($id);

        $attended = $client
            ? $meeting->attendances->where('client_id', $client->id)->first()
            : null;

        return theme_view('portal::chama.meeting_show', compact('meeting', 'client', 'attended'));
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function getClient(): ?Client
    {
        $user = Auth::user();
        if (!$user) return null;

        $clientId = session('client_id')
            ?? ClientUser::where('user_id', $user->id)->value('client_id');

        return $clientId ? Client::find($clientId) : null;
    }

    private function requireClient()
    {
        $client = $this->getClient();
        if (!$client) {
            return redirect('portal/dashboard')->with('error', 'No member profile linked to your account.');
        }
        return $client;
    }
}
