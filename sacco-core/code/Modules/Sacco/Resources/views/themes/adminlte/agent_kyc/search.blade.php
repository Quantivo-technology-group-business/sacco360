@extends('core::layouts.master')
@section('title') Search Agents - KYC @endsection

@section('content')
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1><i class="fas fa-search mr-2"></i>Search Agents</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{url('sacco/agent-kyc')}}">Agent KYC</a></li>
                    <li class="breadcrumb-item active">Search</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<section class="content">
    <div class="card card-primary card-outline">
        <div class="card-header">
            <form action="{{url('sacco/agent-kyc/search')}}" method="get" class="form-inline">
                <div class="input-group mr-2">
                    <input type="text" name="q" class="form-control" value="{{request('q')}}"
                           placeholder="Agent code, number, name, phone...">
                </div>
                <div class="input-group mr-2">
                    <select name="kyc_status" class="form-control">
                        <option value="">All KYC Status</option>
                        <option value="incomplete" {{request('kyc_status') == 'incomplete' ? 'selected' : ''}}>Incomplete</option>
                        <option value="pending_review" {{request('kyc_status') == 'pending_review' ? 'selected' : ''}}>Pending Review</option>
                        <option value="verified" {{request('kyc_status') == 'verified' ? 'selected' : ''}}>Verified</option>
                        <option value="rejected" {{request('kyc_status') == 'rejected' ? 'selected' : ''}}>Rejected</option>
                        <option value="expired" {{request('kyc_status') == 'expired' ? 'selected' : ''}}>Expired</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search mr-1"></i>Search
                </button>
                <a href="{{url('sacco/agent-kyc/search')}}" class="btn btn-secondary ml-2">Clear</a>
            </form>
        </div>
        <div class="card-body p-0">
            @if($agents->count())
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Agent</th>
                        <th>Code / Number</th>
                        <th>Phone</th>
                        <th>Branch</th>
                        <th>Status</th>
                        <th>KYC Status</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($agents as $agent)
                    <tr>
                        <td>
                            <strong>{{$agent->agent_name}}</strong>
                            @if($agent->id_number)
                            <br><small class="text-muted">ID: {{$agent->id_number}}</small>
                            @endif
                        </td>
                        <td>
                            <code>{{$agent->agent_code}}</code><br>
                            <span class="badge badge-primary">Agent #{{$agent->agent_number}}</span>
                        </td>
                        <td>{{$agent->phone ?? '-'}}</td>
                        <td>{{$agent->branch?->name ?? '-'}}</td>
                        <td>{!! $agent->status_badge !!}</td>
                        <td>{!! $agent->kyc_status_badge !!}</td>
                        <td class="text-right">
                            <a href="{{url('sacco/agent-kyc/'.$agent->id.'/show')}}" class="btn btn-primary btn-sm">
                                <i class="fas fa-eye mr-1"></i>Review KYC
                            </a>
                            <a href="{{url('sacco/agency/'.$agent->id.'/show')}}" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-user"></i>
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @else
            <div class="text-center py-5 text-muted">
                <i class="fas fa-search fa-3x mb-3"></i>
                <h5>No agents found</h5>
                <p>Try different search criteria</p>
            </div>
            @endif
        </div>
        @if($agents->hasPages())
        <div class="card-footer">
            {{$agents->appends(request()->query())->links()}}
        </div>
        @endif
    </div>
</section>
@endsection
