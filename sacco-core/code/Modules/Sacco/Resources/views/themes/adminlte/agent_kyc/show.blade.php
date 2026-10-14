@extends('core::layouts.master')
@section('title') Agent KYC - {{$agent->agent_name}} @endsection

@section('content')
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1><i class="fas fa-user-check mr-2"></i>KYC Review</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{url('sacco/agent-kyc')}}">Agent KYC</a></li>
                    <li class="breadcrumb-item active">{{$agent->agent_code}}</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<section class="content">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <i class="fas fa-check-circle mr-2"></i>{{session('success')}}
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <i class="fas fa-exclamation-circle mr-2"></i>{{session('error')}}
        </div>
    @endif

    <div class="row">
        <div class="col-md-4">
            <div class="card card-primary card-outline">
                <div class="card-body box-profile">
                    <div class="text-center">
                        <div class="bg-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-3"
                             style="width: 80px; height: 80px;">
                            <i class="fas fa-user-tie fa-2x text-white"></i>
                        </div>
                        <h3 class="profile-username">{{$agent->agent_name}}</h3>
                        <p class="text-muted mb-1">{{$agent->agent_code}}</p>
                        <p class="text-primary mb-2">
                            <strong>Agent #{{$agent->agent_number}}</strong>
                        </p>
                        {!! $agent->kyc_status_badge !!}
                    </div>

                    <ul class="list-group list-group-unbordered mt-3">
                        <li class="list-group-item">
                            <b><i class="fas fa-phone mr-2 text-muted"></i>Phone</b>
                            <span class="float-right">{{$agent->phone ?? '-'}}</span>
                        </li>
                        <li class="list-group-item">
                            <b><i class="fas fa-id-card mr-2 text-muted"></i>ID Number</b>
                            <span class="float-right">{{$agent->id_number ?? '-'}}</span>
                        </li>
                        <li class="list-group-item">
                            <b><i class="fas fa-building mr-2 text-muted"></i>Branch</b>
                            <span class="float-right">{{$agent->branch?->name ?? '-'}}</span>
                        </li>
                        <li class="list-group-item">
                            <b><i class="fas fa-map-marker-alt mr-2 text-muted"></i>Location</b>
                            <span class="float-right">{{$agent->location ?? '-'}}</span>
                        </li>
                    </ul>

                    <div class="mt-3">
                        <a href="{{url('sacco/agency/'.$agent->id.'/show')}}" class="btn btn-outline-primary btn-block">
                            <i class="fas fa-eye mr-2"></i>View Full Profile
                        </a>
                    </div>
                </div>
            </div>

            <div class="card card-success card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-chart-line mr-2"></i>KYC Progress</h3>
                </div>
                <div class="card-body">
                    <div class="progress mb-2" style="height: 25px;">
                        <div class="progress-bar bg-success" style="width: {{$kycStatus['progress']}}%">
                            {{$kycStatus['progress']}}%
                        </div>
                    </div>
                    <p class="text-center text-muted mb-0">
                        {{$kycStatus['mandatory_met']}} of {{$kycStatus['mandatory_total']}} mandatory requirements met
                    </p>
                </div>
            </div>

            @if($agent->kyc_status === 'verified')
            <div class="card card-success">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-check-circle mr-2"></i>KYC Verified</h3>
                </div>
                <div class="card-body">
                    <p><strong>Verified:</strong> {{$agent->kyc_verified_at?->format('d M Y H:i')}}</p>
                    <p><strong>By:</strong> {{$agent->kycVerifiedBy?->full_name ?? 'System'}}</p>
                    <p><strong>Expires:</strong> {{$agent->kyc_expiry_date?->format('d M Y')}}</p>
                    @if($agent->kyc_notes)
                    <p><strong>Notes:</strong> {{$agent->kyc_notes}}</p>
                    @endif
                </div>
            </div>
            @elseif($agent->kyc_status === 'rejected')
            <div class="card card-danger">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-times-circle mr-2"></i>KYC Rejected</h3>
                </div>
                <div class="card-body">
                    <p><strong>Reason:</strong></p>
                    <p class="text-muted">{{$agent->kyc_notes ?? 'No reason provided'}}</p>
                </div>
            </div>
            @endif
        </div>

        <div class="col-md-8">
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-clipboard-list mr-2"></i>KYC Checklist</h3>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Requirement</th>
                                <th>Status</th>
                                <th>Document #</th>
                                <th>Uploaded</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($kycStatus['checklist'] as $item)
                            @php
                                $req = $item['requirement'];
                                $doc = $item['document'];
                                $status = $item['status'];
                                $rowClass = match($status) {
                                    'approved' => 'table-success',
                                    'pending' => 'table-warning',
                                    'rejected' => 'table-danger',
                                    default => '',
                                };
                            @endphp
                            <tr class="{{$rowClass}}">
                                <td>
                                    <strong>{{$req->name}}</strong>
                                    @if($req->is_mandatory)
                                    <span class="badge badge-danger">Required</span>
                                    @endif
                                    @if($req->requires_verification)
                                    <span class="badge badge-info">Needs Verification</span>
                                    @endif
                                    @if($req->description)
                                    <br><small class="text-muted">{{$req->description}}</small>
                                    @endif
                                </td>
                                <td>
                                    @switch($status)
                                        @case('approved')
                                            <span class="badge badge-success"><i class="fas fa-check mr-1"></i>Approved</span>
                                            @break
                                        @case('pending')
                                            <span class="badge badge-warning"><i class="fas fa-clock mr-1"></i>Pending</span>
                                            @break
                                        @case('rejected')
                                            <span class="badge badge-danger"><i class="fas fa-times mr-1"></i>Rejected</span>
                                            @if($doc?->rejection_reason)
                                            <br><small class="text-danger">{{$doc->rejection_reason}}</small>
                                            @endif
                                            @break
                                        @case('expired')
                                            <span class="badge badge-secondary"><i class="fas fa-calendar-times mr-1"></i>Expired</span>
                                            @break
                                        @default
                                            <span class="badge badge-light"><i class="fas fa-minus mr-1"></i>Missing</span>
                                    @endswitch
                                </td>
                                <td>{{$doc?->document_number ?? '-'}}</td>
                                <td>
                                    @if($doc)
                                    {{$doc->created_at->format('d M Y')}}<br>
                                    <small class="text-muted">{{$doc->uploadedBy?->full_name ?? 'System'}}</small>
                                    @else
                                    -
                                    @endif
                                </td>
                                <td class="text-right">
                                    <div class="btn-group">
                                        @if($doc && $doc->file_path)
                                        <a href="{{$doc->file_url}}" target="_blank" class="btn btn-sm btn-outline-info" title="View Document">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        @endif

                                        @if($doc && $doc->status === 'pending')
                                        <a href="{{url('sacco/agent-kyc/document/'.$doc->id.'/approve')}}" 
                                           class="btn btn-sm btn-success" title="Approve">
                                            <i class="fas fa-check"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-danger" title="Reject"
                                                data-toggle="modal" data-target="#rejectDocModal{{$doc->id}}">
                                            <i class="fas fa-times"></i>
                                        </button>
                                        @endif

                                        <button type="button" class="btn btn-sm btn-primary" title="Upload"
                                                data-toggle="modal" data-target="#uploadModal{{$req->code}}">
                                            <i class="fas fa-upload"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            @if(!in_array($agent->kyc_status, ['verified', 'rejected']))
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            @if($kycStatus['can_approve'])
                            <form action="{{url('sacco/agent-kyc/'.$agent->id.'/approve')}}" method="post" class="d-inline">
                                @csrf
                                <div class="form-group">
                                    <label>Approval Notes (Optional)</label>
                                    <textarea name="notes" class="form-control" rows="2" placeholder="Any notes..."></textarea>
                                </div>
                                <button type="submit" class="btn btn-success btn-lg btn-block">
                                    <i class="fas fa-check-circle mr-2"></i>Approve KYC
                                </button>
                            </form>
                            @else
                            <button class="btn btn-success btn-lg btn-block" disabled>
                                <i class="fas fa-check-circle mr-2"></i>Approve KYC
                            </button>
                            <small class="text-muted">Complete all mandatory requirements to enable approval</small>
                            @endif
                        </div>
                        <div class="col-md-6">
                            <button type="button" class="btn btn-danger btn-lg btn-block" data-toggle="modal" data-target="#rejectKycModal">
                                <i class="fas fa-times-circle mr-2"></i>Reject KYC
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</section>

{{-- Upload Modals --}}
@foreach($requirements as $req)
<div class="modal fade" id="uploadModal{{$req->code}}" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{url('sacco/agent-kyc/'.$agent->id.'/document')}}" method="post" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="document_type" value="{{$req->code}}">
                <div class="modal-header">
                    <h5 class="modal-title">Upload: {{$req->name}}</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Document Number (Optional)</label>
                        <input type="text" name="document_number" class="form-control" placeholder="e.g., ID number, certificate number">
                    </div>
                    <div class="form-group">
                        <label>Document File</label>
                        <div class="custom-file">
                            <input type="file" name="file" class="custom-file-input" accept=".jpg,.jpeg,.png,.pdf">
                            <label class="custom-file-label">Choose file (JPG, PNG, PDF - Max 5MB)</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Upload Document</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach

{{-- Reject Document Modals --}}
@foreach($kycStatus['checklist'] as $item)
@if($item['document'] && $item['document']->status === 'pending')
<div class="modal fade" id="rejectDocModal{{$item['document']->id}}" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{url('sacco/agent-kyc/document/'.$item['document']->id.'/reject')}}" method="post">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Reject Document</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <p>You are rejecting: <strong>{{$item['requirement']->name}}</strong></p>
                    <div class="form-group">
                        <label>Rejection Reason <span class="text-danger">*</span></label>
                        <textarea name="reason" class="form-control" rows="3" required placeholder="Explain why this document is being rejected..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Reject Document</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endforeach

{{-- Reject KYC Modal --}}
<div class="modal fade" id="rejectKycModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{url('sacco/agent-kyc/'.$agent->id.'/reject')}}" method="post">
                @csrf
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Reject Agent KYC</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        This will reject the agent's KYC application. They will need to resubmit documents.
                    </div>
                    <div class="form-group">
                        <label>Rejection Reason <span class="text-danger">*</span></label>
                        <textarea name="reason" class="form-control" rows="4" required placeholder="Explain why KYC is being rejected..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Reject KYC</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function() {
    $('.custom-file-input').on('change', function() {
        let fileName = $(this).val().split('\\').pop();
        $(this).next('.custom-file-label').addClass("selected").html(fileName);
    });
});
</script>
@endpush
