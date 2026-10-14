@extends('core::layouts.master')
@section('title') Investment Plans @endsection
@section('content')
<section class="content-header">
    <div class="container-fluid"><div class="row mb-2">
        <div class="col-sm-6"><h1><i class="fas fa-chart-line mr-2"></i>Investment Plans</h1></div>
        <div class="col-sm-6"><ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="{{url('dashboard')}}">Dashboard</a></li>
            <li class="breadcrumb-item active">Investment Plans</li>
        </ol></div>
    </div></div>
</section>
<section class="content">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Configured Plans</h3>
            <div class="card-tools">
                @can('investment.plans.create')
                <a href="{{url('investment/plan/create')}}" class="btn btn-primary btn-sm"><i class="fas fa-plus mr-1"></i> Add Plan</a>
                @endcan
            </div>
        </div>
        <div class="card-body p-0">
            <table class="table table-hover table-bordered mb-0">
                <thead><tr>
                    <th>Code</th><th>Name</th><th>Type</th><th>Rate</th><th>Method</th>
                    <th>Min Amount</th><th>Duration</th><th>Investments</th><th>Status</th><th>Actions</th>
                </tr></thead>
                <tbody>
                @forelse($plans as $plan)
                <tr>
                    <td><strong>{{$plan->code}}</strong></td>
                    <td><a href="{{url('investment/plan/'.$plan->id.'/show')}}">{{$plan->name}}</a></td>
                    <td>{{$plan->plan_type_label}}</td>
                    <td>@if($plan->return_rate_type==='tiered')<span class="badge badge-info">Tiered</span>@else{{$plan->return_rate}}% p.a.@endif</td>
                    <td>{{ucfirst($plan->interest_method)}}</td>
                    <td>{{number_format($plan->min_amount,2)}}</td>
                    <td>{{$plan->min_duration_months}}@if($plan->max_duration_months)–{{$plan->max_duration_months}}@endif mo</td>
                    <td class="text-center"><span class="badge badge-primary">{{$plan->investments_count}}</span></td>
                    <td>@if($plan->active)<span class="badge badge-success">Active</span>@else<span class="badge badge-secondary">Inactive</span>@endif</td>
                    <td>
                        <div class="btn-group">
                            <a href="{{url('investment/plan/'.$plan->id.'/show')}}" class="btn btn-xs btn-info">View</a>
                            @can('investment.plans.edit')<a href="{{url('investment/plan/'.$plan->id.'/edit')}}" class="btn btn-xs btn-warning">Edit</a>@endcan
                            @can('investment.plans.destroy')<a href="{{url('investment/plan/'.$plan->id.'/destroy')}}" class="btn btn-xs btn-danger confirm">Delete</a>@endcan
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="10" class="text-center text-muted py-4">No plans yet. <a href="{{url('investment/plan/create')}}">Create one.</a></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>
@endsection
