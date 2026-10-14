@extends('core::layouts.master')
@section('title') Maker-Checker Pending Approvals @endsection
@section('content')
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6"><h1>Maker-Checker Approvals</h1></div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item active">Pending Approvals</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<section class="content">
    <div class="container-fluid">
        @if($pending->isEmpty())
            <div class="callout callout-success">
                <h5><i class="fas fa-check-circle"></i> All clear!</h5>
                <p>No pending transactions awaiting checker approval.</p>
            </div>
        @else
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>{{ $pending->total() }} transaction(s)</strong> are awaiting your review.
                You cannot approve transactions you submitted yourself.
            </div>
        @endif

        @forelse($pending as $p)
            @php
                $isSelf = $p->created_by_id === auth()->id();
                $typeLabels = [
                    'loan_approval'          => ['label' => 'Loan Approval',           'icon' => 'fas fa-file-invoice-dollar', 'color' => 'warning'],
                    'loan_disbursement'      => ['label' => 'Loan Disbursement',        'icon' => 'fas fa-hand-holding-usd',   'color' => 'danger'],
                    'loan_writeoff'          => ['label' => 'Loan Write-Off',           'icon' => 'fas fa-times-circle',       'color' => 'danger'],
                    'savings_approval'       => ['label' => 'Savings Approval',         'icon' => 'fas fa-piggy-bank',         'color' => 'info'],
                    'savings_account_closure'=> ['label' => 'Savings Closure',          'icon' => 'fas fa-lock',               'color' => 'danger'],
                    'savings_transaction'    => ['label' => 'Savings Transaction',      'icon' => 'fas fa-exchange-alt',       'color' => 'warning'],
                    'share_approval'         => ['label' => 'Share Approval',           'icon' => 'fas fa-certificate',        'color' => 'info'],
                    'share_deposit'          => ['label' => 'Share Purchase',           'icon' => 'fas fa-plus-circle',        'color' => 'success'],
                    'share_withdrawal'       => ['label' => 'Share Redemption',         'icon' => 'fas fa-minus-circle',       'color' => 'danger'],
                    'payroll_approval'       => ['label' => 'Payroll Approval',         'icon' => 'fas fa-users',              'color' => 'warning'],
                    'expense'                => ['label' => 'Expense Approval',         'icon' => 'fas fa-receipt',            'color' => 'warning'],
                    'client_activation'      => ['label' => 'Client Activation',        'icon' => 'fas fa-user-check',         'color' => 'info'],
                    'journal_entry'          => ['label' => 'Journal Entry',            'icon' => 'fas fa-book',               'color' => 'secondary'],
                    'dividend'               => ['label' => 'Dividend',                 'icon' => 'fas fa-percentage',         'color' => 'success'],
                    'provision_run'          => ['label' => 'Provision Run',            'icon' => 'fas fa-cogs',               'color' => 'secondary'],
                    'classification_override'=> ['label' => 'Classification Override',  'icon' => 'fas fa-tag',                'color' => 'secondary'],
                ];
                $meta = $typeLabels[$p->approvable_type] ?? ['label' => ucwords(str_replace('_', ' ', $p->approvable_type)), 'icon' => 'fas fa-question-circle', 'color' => 'secondary'];
            @endphp
            <div class="card card-outline card-{{ $meta['color'] }} mb-3">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="{{ $meta['icon'] }} mr-1"></i>
                        {{ $p->label ?? $meta['label'] }}
                        <span class="badge badge-{{ $meta['color'] }} ml-2">{{ $meta['label'] }}</span>
                    </h3>
                    <div class="card-tools">
                        <span class="text-muted small">{{ $p->created_at->diffForHumans() }}</span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <dl class="row mb-0">
                                <dt class="col-sm-5 text-muted">Amount</dt>
                                <dd class="col-sm-7"><strong>{{ $p->amount > 0 ? number_format($p->amount, 2) : '—' }}</strong></dd>
                                <dt class="col-sm-5 text-muted">Submitted by</dt>
                                <dd class="col-sm-7">
                                    {{ optional($p->creator)->first_name }} {{ optional($p->creator)->last_name }}
                                    @if($isSelf)
                                        <span class="badge badge-danger ml-1">You</span>
                                    @endif
                                </dd>
                                <dt class="col-sm-5 text-muted">Record #</dt>
                                <dd class="col-sm-7">#{{ $p->approvable_id }}</dd>
                            </dl>
                        </div>
                        <div class="col-md-8">
                            @if($p->notes)
                                <p class="text-muted mb-1"><i class="fas fa-comment-alt mr-1"></i> {{ $p->notes }}</p>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    @if($isSelf)
                        <div class="alert alert-danger mb-0 py-2">
                            <i class="fas fa-ban mr-1"></i>
                            <strong>Self-approval not permitted.</strong> You submitted this transaction. A different user must approve it.
                        </div>
                    @else
                        <a href="{{ url('sacco/approval/'.$p->id.'/approve') }}"
                           class="btn btn-success confirm"
                           data-confirm="Approve this {{ $meta['label'] }}? This action will execute immediately.">
                            <i class="fas fa-check mr-1"></i> Approve
                        </a>

                        <button type="button" class="btn btn-danger ml-2"
                                data-toggle="modal" data-target="#declineModal{{ $p->id }}">
                            <i class="fas fa-times mr-1"></i> Decline
                        </button>
                    @endif
                </div>
            </div>

            {{-- Decline modal --}}
            <div class="modal fade" id="declineModal{{ $p->id }}" tabindex="-1" role="dialog">
                <div class="modal-dialog" role="document">
                    <form method="post" action="{{ url('sacco/approval/'.$p->id.'/decline') }}">
                        @csrf
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Decline: {{ $p->label ?? $meta['label'] }}</h5>
                                <button type="button" class="close" data-dismiss="modal">&times;</button>
                            </div>
                            <div class="modal-body">
                                <div class="form-group">
                                    <label>Reason for declining <span class="text-danger">*</span></label>
                                    <textarea name="notes" class="form-control" rows="3" required placeholder="Provide a reason..."></textarea>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-danger">Confirm Decline</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        @empty
        @endforelse

        {{ $pending->links() }}
    </div>
</section>
@endsection
