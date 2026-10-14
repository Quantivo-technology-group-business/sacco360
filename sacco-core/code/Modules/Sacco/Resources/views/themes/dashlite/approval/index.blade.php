@extends('core::layouts.master')
@section('title') Maker-Checker Approvals @endsection
@section('content')

<div class="nk-block-head nk-block-head-sm mb-4">
    <div class="nk-block-between">
        <div class="nk-block-head-content">
            <h3 class="nk-block-title page-title">Maker-Checker Approvals</h3>
            <div class="nk-block-des text-soft">
                <p>Review and action pending transactions submitted by other users.</p>
            </div>
        </div>
        <div class="nk-block-head-content">
            <span class="badge badge-{{ $pending->total() > 0 ? 'warning' : 'success' }} badge-lg">
                {{ $pending->total() }} Pending
            </span>
        </div>
    </div>
</div>

@php
$typeMap = [
    'loan_approval'           => ['label' => 'Loan Approval',           'icon' => 'ni-file-docs',      'color' => 'warning'],
    'loan_disbursement'       => ['label' => 'Loan Disbursement',       'icon' => 'ni-money',          'color' => 'danger'],
    'loan_writeoff'           => ['label' => 'Loan Write-Off',          'icon' => 'ni-cross-circle',   'color' => 'danger'],
    'loan_repayment'          => ['label' => 'Loan Repayment',          'icon' => 'ni-arrow-up-circle','color' => 'info'],
    'savings_approval'        => ['label' => 'Savings Approval',        'icon' => 'ni-coins',          'color' => 'info'],
    'savings_account_closure' => ['label' => 'Savings Closure',         'icon' => 'ni-lock-alt',       'color' => 'danger'],
    'savings_transaction'     => ['label' => 'Savings Transaction',     'icon' => 'ni-exchange-v',     'color' => 'warning'],
    'share_approval'          => ['label' => 'Share Approval',          'icon' => 'ni-star',           'color' => 'info'],
    'share_deposit'           => ['label' => 'Share Purchase',          'icon' => 'ni-plus-circle',    'color' => 'success'],
    'share_withdrawal'        => ['label' => 'Share Redemption',        'icon' => 'ni-minus-circle',   'color' => 'danger'],
    'payroll_approval'        => ['label' => 'Payroll Approval',        'icon' => 'ni-users',          'color' => 'warning'],
    'expense'                 => ['label' => 'Expense Approval',        'icon' => 'ni-report',         'color' => 'warning'],
    'client_activation'       => ['label' => 'Client Activation',       'icon' => 'ni-user-check',     'color' => 'info'],
    'journal_entry'           => ['label' => 'Journal Entry',           'icon' => 'ni-book-read',      'color' => 'secondary'],
    'dividend'                => ['label' => 'Dividend',                'icon' => 'ni-growth',         'color' => 'success'],
    'provision_run'           => ['label' => 'Provision Run',           'icon' => 'ni-setting',        'color' => 'secondary'],
    'classification_override' => ['label' => 'Classification Override', 'icon' => 'ni-tag',            'color' => 'secondary'],
];
@endphp

@if($pending->isEmpty())
    <div class="nk-block">
        <div class="alert alert-success d-flex align-items-center">
            <em class="icon ni ni-check-circle-cut fs-22px mr-3"></em>
            <div>
                <strong>All clear!</strong>
                <span class="d-block text-soft">No transactions are pending your review.</span>
            </div>
        </div>
    </div>
@else
    <div class="nk-block mb-3">
        <div class="alert alert-warning d-flex align-items-center">
            <em class="icon ni ni-alert-circle fs-22px mr-3"></em>
            <div>
                <strong>{{ $pending->total() }} transaction(s) pending review.</strong>
                <span class="d-block text-soft">You cannot approve transactions you submitted yourself.</span>
            </div>
        </div>
    </div>
@endif

<div class="nk-block">
    @forelse($pending as $p)
        @php
            $isSelf = $p->created_by_id === auth()->id();
            $meta = $typeMap[$p->approvable_type] ?? [
                'label' => ucwords(str_replace('_', ' ', $p->approvable_type)),
                'icon'  => 'ni-question-alt',
                'color' => 'secondary',
            ];
        @endphp

        <div class="card card-bordered mb-3 {{ $isSelf ? 'border-left border-danger' : '' }}">
            <div class="card-inner">
                {{-- Header row --}}
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div class="d-flex align-items-center">
                        <div class="icon-wrap icon-wrap-sm bg-{{ $meta['color'] }}-dim rounded mr-3"
                             style="width:40px;height:40px;display:flex;align-items:center;justify-content:center;">
                            <em class="icon ni {{ $meta['icon'] }} text-{{ $meta['color'] }} fs-18px"></em>
                        </div>
                        <div>
                            <h6 class="mb-0">{{ $p->label ?? $meta['label'] }}</h6>
                            <span class="badge badge-dim badge-{{ $meta['color'] }}">{{ $meta['label'] }}</span>
                        </div>
                    </div>
                    <div class="text-right">
                        <span class="text-soft small">{{ $p->created_at->diffForHumans() }}</span>
                        @if($p->amount > 0)
                            <div class="fw-bold text-dark">{{ number_format($p->amount, 2) }}</div>
                        @endif
                    </div>
                </div>

                {{-- Details row --}}
                <div class="row g-3 mb-3">
                    <div class="col-sm-4">
                        <span class="text-soft small d-block">Submitted by</span>
                        <span class="fw-medium">
                            {{ optional($p->creator)->first_name }} {{ optional($p->creator)->last_name }}
                            @if($isSelf)
                                <span class="badge badge-danger ml-1">You</span>
                            @endif
                        </span>
                    </div>
                    <div class="col-sm-4">
                        <span class="text-soft small d-block">Record #</span>
                        <span class="fw-medium">#{{ $p->approvable_id }}</span>
                    </div>
                    <div class="col-sm-4">
                        <span class="text-soft small d-block">Queued at</span>
                        <span class="fw-medium">{{ $p->created_at->format('d M Y H:i') }}</span>
                    </div>
                </div>

                @if($p->notes)
                    <div class="mb-3">
                        <span class="text-soft small d-block mb-1">Notes</span>
                        <p class="text-muted mb-0">{{ $p->notes }}</p>
                    </div>
                @endif

                {{-- Action bar --}}
                @if($isSelf)
                    <div class="alert alert-danger mb-0 py-2 d-flex align-items-center">
                        <em class="icon ni ni-na mr-2 text-danger"></em>
                        <span><strong>Self-approval not permitted.</strong> A different user must review this transaction.</span>
                    </div>
                @else
                    <div class="d-flex gap-2 align-items-center">
                        <a href="{{ url('sacco/approval/'.$p->id.'/approve') }}"
                           class="btn btn-success confirm"
                           data-confirm="Approve this {{ $meta['label'] }}? The action will be executed immediately.">
                            <em class="icon ni ni-check mr-1"></em> Approve
                        </a>
                        <button type="button" class="btn btn-danger"
                                data-toggle="modal" data-target="#declineModal{{ $p->id }}">
                            <em class="icon ni ni-cross mr-1"></em> Decline
                        </button>
                        @if(in_array($p->approvable_type, ['loan_approval','loan_disbursement','loan_writeoff','loan_repayment']))
                            <a href="{{ url('loan/'.$p->approvable_id.'/show') }}"
                               class="btn btn-outline-secondary btn-sm" target="_blank">
                                <em class="icon ni ni-eye"></em> View Loan
                            </a>
                        @elseif(in_array($p->approvable_type, ['savings_approval','savings_account_closure','savings_transaction']))
                            <a href="{{ url('savings/'.$p->approvable_id.'/show') }}"
                               class="btn btn-outline-secondary btn-sm" target="_blank">
                                <em class="icon ni ni-eye"></em> View Savings
                            </a>
                        @elseif(in_array($p->approvable_type, ['share_approval','share_deposit','share_withdrawal']))
                            <a href="{{ url('share/'.$p->approvable_id.'/show') }}"
                               class="btn btn-outline-secondary btn-sm" target="_blank">
                                <em class="icon ni ni-eye"></em> View Share
                            </a>
                        @elseif($p->approvable_type === 'client_activation')
                            <a href="{{ url('client/'.$p->approvable_id.'/show') }}"
                               class="btn btn-outline-secondary btn-sm" target="_blank">
                                <em class="icon ni ni-eye"></em> View Client
                            </a>
                        @elseif($p->approvable_type === 'payroll_approval')
                            <a href="{{ url('payroll/run/'.$p->approvable_id.'/show') }}"
                               class="btn btn-outline-secondary btn-sm" target="_blank">
                                <em class="icon ni ni-eye"></em> View Payroll Run
                            </a>
                        @elseif($p->approvable_type === 'expense')
                            <a href="{{ url('expense/'.$p->approvable_id.'/show') }}"
                               class="btn btn-outline-secondary btn-sm" target="_blank">
                                <em class="icon ni ni-eye"></em> View Expense
                            </a>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        {{-- Decline Modal --}}
        <div class="modal fade" id="declineModal{{ $p->id }}" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <form method="post" action="{{ url('sacco/approval/'.$p->id.'/decline') }}">
                    @csrf
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                <em class="icon ni ni-cross-circle text-danger mr-1"></em>
                                Decline: {{ $p->label ?? $meta['label'] }}
                            </h5>
                            <a href="#" class="close" data-dismiss="modal"><em class="icon ni ni-cross"></em></a>
                        </div>
                        <div class="modal-body">
                            <div class="alert alert-warning">
                                <em class="icon ni ni-alert-circle mr-1"></em>
                                Declining will cancel this transaction. This action cannot be undone.
                            </div>
                            <div class="form-group">
                                <label class="form-label">Reason for declining <span class="text-danger">*</span></label>
                                <textarea name="notes" class="form-control" rows="3"
                                          required placeholder="Provide a clear reason for declining..."></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <a href="#" class="link link-light" data-dismiss="modal">Cancel</a>
                            <button type="submit" class="btn btn-danger">Confirm Decline</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @empty
    @endforelse

    <div class="mt-4">{{ $pending->links() }}</div>
</div>

@endsection
