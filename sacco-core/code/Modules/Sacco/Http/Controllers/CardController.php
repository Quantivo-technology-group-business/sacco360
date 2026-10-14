<?php

namespace Modules\Sacco\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Sacco\Entities\SaccoDebitCard;
use Modules\Sacco\Entities\SaccoCardTransaction;
use Modules\Sacco\Entities\SaccoCardDispute;
use Modules\Sacco\Entities\SaccoCardRequest;
use Modules\Sacco\Services\CardService;
use Modules\Client\Entities\Client;
use Modules\Savings\Entities\Savings;

class CardController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', '2fa']);
        $this->middleware('permission:sacco.cards.index')->only(['index','show','transactions','disputes']);
        $this->middleware('permission:sacco.cards.manage')->only([
            'create','store','block','activate','cancel','updateLimits','updateChannels',
            'updateDelivery','resetPin','unlockPin','setPinSet','reportLost','recordTransaction',
        ]);
        $this->middleware('permission:sacco.cards.requests')->only([
            'requests','createRequest','storeRequest','approveRequest','rejectRequest',
        ]);
        $this->middleware('permission:sacco.cards.disputes')->only(['storeDispute','resolveDispute']);
    }

    public function index(Request $request)
    {
        $query = SaccoDebitCard::with('client')->orderByDesc('id');
        if ($request->filled('status'))    $query->where('status', $request->status);
        if ($request->filled('card_type')) $query->where('card_type', $request->card_type);
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function($q) use($s){
                $q->where('card_number','like',"%$s%")
                  ->orWhereHas('client', fn($cq)=>$cq->where('first_name','like',"%$s%")
                    ->orWhere('last_name','like',"%$s%")
                    ->orWhere('member_number','like',"%$s%"));
            });
        }
        $cards = $query->paginate(30);
        $stats = [
            'total'    => SaccoDebitCard::count(),
            'active'   => SaccoDebitCard::where('status','active')->count(),
            'blocked'  => SaccoDebitCard::where('status','blocked')->count(),
            'pending'  => SaccoDebitCard::where('status','pending')->count(),
            'expiring' => SaccoDebitCard::where('status','active')
                ->whereBetween('expiry_date',[now(),now()->addDays(60)])->count(),
        ];
        return theme_view('sacco::cards.index', compact('cards','stats'));
    }

    public function show($id)
    {
        $card = SaccoDebitCard::with(['client','savings','pinLogs'])->findOrFail($id);
        $recentTransactions = SaccoCardTransaction::where('sacco_debit_card_id',$id)
            ->orderByDesc('transaction_at')->limit(10)->get();
        $openDisputes  = SaccoCardDispute::where('sacco_debit_card_id',$id)
            ->whereIn('status',['submitted','under_investigation'])->count();
        $monthlySpend  = $card->getMonthlySpend();
        $todaySpend    = $card->getTodaySpend();
        return theme_view('sacco::cards.show',compact('card','recentTransactions','openDisputes','monthlySpend','todaySpend'));
    }

    public function create(Request $request)
    {
        $clients = Client::where('status',1)->orderBy('first_name')->get();
        $client  = $request->filled('client_id') ? Client::with('savings')->find($request->client_id) : null;
        return theme_view('sacco::cards.create', compact('clients','client'));
    }

    public function store(Request $request, CardService $service)
    {
        $request->validate([
            'client_id'    => 'required|exists:clients,id',
            'savings_id'   => 'required|exists:savings,id',
            'card_type'    => 'required|in:physical,virtual',
            'card_network' => 'required|in:visa,mastercard,local',
        ]);
        $existing = SaccoDebitCard::where('client_id',$request->client_id)
            ->whereIn('status',['pending','active'])->first();
        if ($existing) {
            \flash('Member already has an active card (****'.substr($existing->card_number,-4).'). Block/cancel it first.')->error()->important();
            return back()->withInput();
        }
        $req  = $service->submitRequest(array_merge($request->only(['client_id','savings_id','card_type','card_network','delivery_address','card_fee']),
            ['created_by_id'=>Auth::id(),'request_type'=>'new','preferred_name'=>$request->card_name,'delivery_method'=>$request->delivery_method??'branch_pickup']));
        $card = $service->approveRequest($req, Auth::id());
        \flash('Card issued: '.$card->masked_number)->success()->important();
        return redirect('sacco/cards/'.$card->id.'/show');
    }

    public function block(Request $request, $id, CardService $service)
    {
        $card = SaccoDebitCard::findOrFail($id);
        $service->block($card, $request->blocked_reason ?? 'Blocked by staff', Auth::id());
        \flash('Card blocked.')->warning()->important();
        return redirect('sacco/cards/'.$id.'/show');
    }

    public function activate($id, CardService $service)
    {
        $service->activate(SaccoDebitCard::findOrFail($id), Auth::id());
        \flash('Card activated.')->success()->important();
        return redirect('sacco/cards/'.$id.'/show');
    }

    public function cancel(Request $request, $id, CardService $service)
    {
        $request->validate(['cancelled_reason'=>'required|string|max:200']);
        $service->cancel(SaccoDebitCard::findOrFail($id), $request->cancelled_reason, Auth::id());
        \flash('Card cancelled.')->danger()->important();
        return redirect('sacco/cards');
    }

    public function reportLost(Request $request, $id, CardService $service)
    {
        $request->validate(['reason'=>'required|in:lost,stolen']);
        $card   = SaccoDebitCard::findOrFail($id);
        $newReq = $service->reportLostStolen($card, $request->reason, Auth::id());
        \flash("Card blocked. Replacement request #{$newReq->id} auto-created.")->warning()->important();
        return redirect('sacco/cards/'.$id.'/show');
    }

    public function updateLimits(Request $request, $id, CardService $service)
    {
        $request->validate(['daily_limit'=>'required|numeric|min:0','atm_daily_limit'=>'required|numeric|min:0',
            'pos_daily_limit'=>'required|numeric|min:0','online_daily_limit'=>'required|numeric|min:0',
            'contactless_limit'=>'required|numeric|min:0','monthly_limit'=>'nullable|numeric|min:0']);
        $service->updateLimits(SaccoDebitCard::findOrFail($id), $request->only([
            'daily_limit','atm_daily_limit','pos_daily_limit','online_daily_limit','contactless_limit','monthly_limit']));
        \flash('Limits updated.')->success()->important();
        return redirect('sacco/cards/'.$id.'/show');
    }

    public function updateChannels(Request $request, $id, CardService $service)
    {
        $card = SaccoDebitCard::findOrFail($id);
        $service->updateChannels($card, [
            'atm_enabled'           => $request->boolean('atm_enabled')           ? 1 : 0,
            'pos_enabled'           => $request->boolean('pos_enabled')           ? 1 : 0,
            'online_enabled'        => $request->boolean('online_enabled')        ? 1 : 0,
            'contactless_enabled'   => $request->boolean('contactless_enabled')   ? 1 : 0,
            'international_enabled' => $request->boolean('international_enabled') ? 1 : 0,
        ]);
        \flash('Channel settings updated.')->success()->important();
        return redirect('sacco/cards/'.$id.'/show');
    }

    public function updateDelivery(Request $request, $id, CardService $service)
    {
        $request->validate(['delivery_status'=>'required|in:not_dispatched,dispatched,delivered,returned']);
        $service->updateDelivery(SaccoDebitCard::findOrFail($id), $request->delivery_status, $request->delivery_date);
        \flash('Delivery status updated.')->success()->important();
        return redirect('sacco/cards/'.$id.'/show');
    }

    public function setPinSet(Request $request, $id, CardService $service)
    {
        $service->markPinSet(SaccoDebitCard::findOrFail($id), Auth::id(), $request->channel ?? 'branch');
        \flash('PIN marked as set.')->success()->important();
        return redirect('sacco/cards/'.$id.'/show');
    }

    public function resetPin($id, CardService $service)
    {
        $service->resetPin(SaccoDebitCard::findOrFail($id), Auth::id());
        \flash('PIN reset. Member must set a new PIN.')->warning()->important();
        return redirect('sacco/cards/'.$id.'/show');
    }

    public function unlockPin($id, CardService $service)
    {
        $service->unlockPin(SaccoDebitCard::findOrFail($id), Auth::id());
        \flash('PIN unlocked.')->success()->important();
        return redirect('sacco/cards/'.$id.'/show');
    }

    public function transactions(Request $request, $id)
    {
        $card  = SaccoDebitCard::with('client')->findOrFail($id);
        $query = SaccoCardTransaction::where('sacco_debit_card_id',$id)->orderByDesc('transaction_at');
        if ($request->filled('channel')) $query->where('channel',$request->channel);
        if ($request->filled('status'))  $query->where('status',$request->status);
        if ($request->filled('from'))    $query->whereDate('transaction_at','>=',$request->from);
        if ($request->filled('to'))      $query->whereDate('transaction_at','<=',$request->to);
        $transactions = $query->paginate(30);
        $totalSpend   = SaccoCardTransaction::where('sacco_debit_card_id',$id)->where('status','approved')->whereIn('transaction_type',['purchase','withdrawal'])->sum('amount');
        $totalFees    = SaccoCardTransaction::where('sacco_debit_card_id',$id)->sum('fee_amount');
        return theme_view('sacco::cards.transactions', compact('card','transactions','totalSpend','totalFees'));
    }

    public function recordTransaction(Request $request, $id, CardService $service)
    {
        $request->validate(['channel'=>'required','transaction_type'=>'required','amount'=>'required|numeric|min:0.01','transaction_at'=>'required|date']);
        $card = SaccoDebitCard::findOrFail($id);
        $service->recordTransaction($card, array_merge($request->only(['channel','transaction_type','amount','fee_amount','merchant_name','merchant_category','merchant_location','terminal_id','external_reference','transaction_at','currency']),['status'=>'approved']));
        \flash('Transaction recorded.')->success()->important();
        return redirect('sacco/cards/'.$id.'/transactions');
    }

    public function disputes(Request $request, $id)
    {
        $card             = SaccoDebitCard::with('client')->findOrFail($id);
        $disputes         = SaccoCardDispute::where('sacco_debit_card_id',$id)->orderByDesc('id')->paginate(20);
        $cardTransactions = SaccoCardTransaction::where('sacco_debit_card_id',$id)->orderByDesc('transaction_at')->limit(50)->get();
        return theme_view('sacco::cards.disputes', compact('card','disputes','cardTransactions'));
    }

    public function storeDispute(Request $request, $id, CardService $service)
    {
        $request->validate(['dispute_type'=>'required','disputed_amount'=>'required|numeric|min:0.01','description'=>'required|string']);
        $card    = SaccoDebitCard::findOrFail($id);
        $dispute = $service->submitDispute($card, array_merge($request->only(['dispute_type','disputed_amount','description','transaction_date','merchant_name','sacco_card_transaction_id']),['created_by_id'=>Auth::id()]));
        \flash('Dispute '.$dispute->dispute_reference.' submitted.')->success()->important();
        return redirect('sacco/cards/'.$id.'/disputes');
    }

    public function resolveDispute(Request $request, $id, $disputeId, CardService $service)
    {
        $request->validate(['outcome'=>'required|in:resolved_in_favour,resolved_against','resolution_notes'=>'required|string']);
        $dispute = SaccoCardDispute::findOrFail($disputeId);
        $service->resolveDispute($dispute,$request->outcome,$request->settled_amount?(float)$request->settled_amount:null,$request->resolution_notes,Auth::id());
        \flash('Dispute resolved.')->success()->important();
        return redirect('sacco/cards/'.$id.'/disputes');
    }

    public function requests(Request $request)
    {
        $requests = SaccoCardRequest::with('client')->orderByDesc('id')->paginate(30);
        return theme_view('sacco::cards.requests', compact('requests'));
    }

    public function createRequest(Request $request)
    {
        $clients = Client::where('status',1)->orderBy('first_name')->get();
        $client  = $request->filled('client_id') ? Client::with(['savings', 'branch', 'profession'])->find($request->client_id) : null;
        return theme_view('sacco::cards.create_request', compact('clients','client'));
    }

    public function getClientDetails($clientId)
    {
        $client = Client::with(['savings', 'branch', 'profession', 'identifications'])
            ->findOrFail($clientId);
        
        $existingCards = SaccoDebitCard::where('client_id', $clientId)
            ->whereIn('status', ['pending', 'active', 'blocked'])
            ->get(['id', 'card_number', 'status', 'card_type', 'expiry_date']);

        return response()->json([
            'id'            => $client->id,
            'member_number' => $client->member_number ?? $client->external_id ?? $client->id,
            'full_name'     => trim($client->first_name . ' ' . $client->middle_name . ' ' . $client->last_name),
            'first_name'    => $client->first_name,
            'middle_name'   => $client->middle_name,
            'last_name'     => $client->last_name,
            'gender'        => ucfirst($client->gender ?? 'N/A'),
            'dob'           => $client->dob ? \Carbon\Carbon::parse($client->dob)->format('d M Y') : 'N/A',
            'age'           => $client->dob ? \Carbon\Carbon::parse($client->dob)->age . ' years' : 'N/A',
            'marital_status'=> ucfirst(str_replace('_', ' ', $client->marital_status ?? 'N/A')),
            'mobile'        => $client->mobile ?? 'N/A',
            'phone'         => $client->phone ?? 'N/A',
            'email'         => $client->email ?? 'N/A',
            'address'       => $client->address ?? 'N/A',
            'city'          => $client->city ?? 'N/A',
            'state'         => $client->state ?? 'N/A',
            'zip'           => $client->zip ?? 'N/A',
            'employer'      => $client->employer ?? 'N/A',
            'branch'        => optional($client->branch)->name ?? 'N/A',
            'profession'    => optional($client->profession)->name ?? 'N/A',
            'photo'         => $client->photo ? asset('storage/uploads/clients/' . $client->photo) : null,
            'status'        => ucfirst($client->status ?? 'N/A'),
            'created_date'  => $client->created_date ? \Carbon\Carbon::parse($client->created_date)->format('d M Y') : 'N/A',
            'savings'       => $client->savings->map(fn($s) => [
                'id'             => $s->id,
                'account_number' => $s->account_number,
                'balance'        => number_format($s->balance, 2),
                'status'         => $s->status,
            ]),
            'identifications' => $client->identifications->map(fn($i) => [
                'type'   => $i->identification_type ?? 'ID',
                'number' => $i->identification_value ?? $i->identification_number ?? 'N/A',
            ]),
            'existing_cards' => $existingCards->map(fn($c) => [
                'id'          => $c->id,
                'masked'      => '****' . substr($c->card_number, -4),
                'status'      => ucfirst($c->status),
                'type'        => ucfirst($c->card_type),
                'expiry'      => $c->expiry_date ? \Carbon\Carbon::parse($c->expiry_date)->format('M Y') : 'N/A',
            ]),
        ]);
    }

    public function storeRequest(Request $request, CardService $service)
    {
        $request->validate(['client_id'=>'required|exists:clients,id','request_type'=>'required|in:new,replacement,virtual']);
        $service->submitRequest(array_merge($request->only(['client_id','savings_id','request_type','replacement_reason','replaced_card_id','preferred_name','delivery_address','delivery_method','card_network','card_type','card_fee','fee_waived','notes']),['created_by_id'=>Auth::id()]));
        \flash('Card request submitted.')->success()->important();
        return redirect('sacco/cards/requests');
    }

    public function approveRequest($requestId, CardService $service)
    {
        $req  = SaccoCardRequest::findOrFail($requestId);
        $card = $service->approveRequest($req, Auth::id());
        \flash('Request approved. Card: '.$card->masked_number)->success()->important();
        return redirect('sacco/cards/'.$card->id.'/show');
    }

    public function rejectRequest(Request $request, $requestId)
    {
        $request->validate(['notes'=>'required|string']);
        $req = SaccoCardRequest::findOrFail($requestId);
        $req->status = 'rejected'; $req->notes = $request->notes; $req->save();
        \flash('Request rejected.')->warning()->important();
        return redirect('sacco/cards/requests');
    }
}
