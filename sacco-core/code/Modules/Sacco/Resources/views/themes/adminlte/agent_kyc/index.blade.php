@extends('core::layouts.master')
@section('title') Agent KYC Dashboard @endsection
@section('styles')
    <link rel="stylesheet" href="{{ asset('assets/plugins/datatables.net-bs/css/dataTables.bootstrap.min.css') }}">
@stop
@section('content')
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1><i class="fas fa-user-check mr-2"></i>Agent KYC</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{url('sacco/agency')}}">Agency Banking</a></li>
                    <li class="breadcrumb-item active">KYC</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<section class="content">
    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{$stats['total_agents']}}</h3>
                    <p>Total Agents</p>
                </div>
                <div class="icon"><i class="fas fa-users"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{$stats['kyc_verified']}}</h3>
                    <p>KYC Verified</p>
                </div>
                <div class="icon"><i class="fas fa-check-circle"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{$stats['kyc_pending']}}</h3>
                    <p>Pending Review</p>
                </div>
                <div class="icon"><i class="fas fa-clock"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3>{{$stats['pending_documents']}}</h3>
                    <p>Pending Documents</p>
                </div>
                <div class="icon"><i class="fas fa-file-alt"></i></div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-search mr-2"></i>Quick Search</h3>
                </div>
                <div class="card-body">
                    <form action="{{url('sacco/agent-kyc/search')}}" method="get">
                        <div class="form-group">
                            <input type="text" name="q" class="form-control" placeholder="Agent code, number, name, phone...">
                        </div>
                        <div class="form-group">
                            <select name="kyc_status" class="form-control">
                                <option value="">All KYC Status</option>
                                <option value="incomplete">Incomplete</option>
                                <option value="pending_review">Pending Review</option>
                                <option value="verified">Verified</option>
                                <option value="rejected">Rejected</option>
                                <option value="expired">Expired</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fas fa-search mr-2"></i>Search Agents
                        </button>
                    </form>
                </div>
            </div>

            <div class="card card-secondary card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-chart-pie mr-2"></i>KYC Status</h3>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <tr class="bg-light">
                            <td><span class="badge badge-secondary">Incomplete</span></td>
                            <td class="text-right"><strong>{{$stats['kyc_incomplete']}}</strong></td>
                        </tr>
                        <tr>
                            <td><span class="badge badge-warning">Pending Review</span></td>
                            <td class="text-right"><strong>{{$stats['kyc_pending']}}</strong></td>
                        </tr>
                        <tr class="bg-light">
                            <td><span class="badge badge-success">Verified</span></td>
                            <td class="text-right"><strong>{{$stats['kyc_verified']}}</strong></td>
                        </tr>
                        <tr>
                            <td><span class="badge badge-danger">Rejected</span></td>
                            <td class="text-right"><strong>{{$stats['kyc_rejected']}}</strong></td>
                        </tr>
                        <tr class="bg-light">
                            <td><span class="badge badge-secondary">Expired</span></td>
                            <td class="text-right"><strong>{{$stats['kyc_expired']}}</strong></td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <a href="{{url('sacco/agent-kyc/requirements')}}" class="btn btn-outline-primary btn-block">
                        <i class="fas fa-cog mr-2"></i>Manage Requirements
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card card-warning card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-clock mr-2"></i>Agents Pending Review</h3>
                    <div class="card-tools">
                        <a href="{{url('sacco/agent-kyc/search?kyc_status=pending_review')}}" class="btn btn-tool">
                            View All <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    @if($pendingAgents->count())
                    <table id="pending-agents-table" class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Agent</th>
                                <th>Code / Number</th>
                                <th>Branch</th>
                                <th>KYC Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pendingAgents as $agent)
                            <tr>
                                <td>
                                    <strong>{{$agent->agent_name}}</strong><br>
                                    <small class="text-muted">{{$agent->phone}}</small>
                                </td>
                                <td>
                                    <code>{{$agent->agent_code}}</code><br>
                                    <small class="text-primary">Agent #{{$agent->agent_number}}</small>
                                </td>
                                <td>{{$agent->branch?->name ?? '-'}}</td>
                                <td>{!! $agent->kyc_status_badge !!}</td>
                                <td class="text-right">
                                    <a href="{{url('sacco/agent-kyc/'.$agent->id.'/show')}}" class="btn btn-primary btn-sm">
                                        <i class="fas fa-eye mr-1"></i>Review
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @else
                    <div class="text-center py-4 text-muted">
                        <i class="fas fa-check-circle fa-3x mb-3"></i>
                        <p>No agents pending KYC review</p>
                    </div>
                    @endif
                </div>
            </div>

            <div class="card card-info card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-file-upload mr-2"></i>Recent Document Uploads</h3>
                </div>
                <div class="card-body p-0">
                    @if($recentDocuments->count())
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Document</th>
                                <th>Agent</th>
                                <th>Uploaded By</th>
                                <th>Date</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentDocuments as $doc)
                            <tr>
                                <td>
                                    <strong>{{$doc->document_type_label}}</strong>
                                    {!! $doc->status_badge !!}
                                </td>
                                <td>
                                    <a href="{{url('sacco/agent-kyc/'.$doc->sacco_agent_id.'/show')}}">
                                        {{$doc->agent?->agent_name}}
                                    </a>
                                </td>
                                <td>{{$doc->uploadedBy?->full_name ?? 'System'}}</td>
                                <td>{{$doc->created_at->diffForHumans()}}</td>
                                <td class="text-right">
                                    <a href="{{url('sacco/agent-kyc/'.$doc->sacco_agent_id.'/show')}}" class="btn btn-sm btn-outline-primary">
                                        Review
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @else
                    <div class="text-center py-4 text-muted">
                        <i class="fas fa-inbox fa-3x mb-3"></i>
                        <p>No recent document uploads</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
@section('scripts')
    <script src="{{ asset('assets/plugins/datatables.net/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/datatables.net-bs/js/dataTables.bootstrap.min.js') }}"></script>
    <script>
        @if($pendingAgents->count())
        $('#pending-agents-table').DataTable({
            pageLength: 10,
            order: [[0, 'asc']],
            responsive: true,
            autoWidth: false,
        });
        @endif
    </script>
@endsection
