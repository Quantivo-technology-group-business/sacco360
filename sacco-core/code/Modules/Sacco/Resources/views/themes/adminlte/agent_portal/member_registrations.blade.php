@extends('core::layouts.master')
@section('title') Agent Portal - Member Registrations @endsection
@section('content')

<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1><i class="fas fa-users mr-2"></i>My Member Registrations</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{route('agent.dashboard')}}">Agent Portal</a></li>
                    <li class="breadcrumb-item active">Member Registrations</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<section class="content">
    <div class="row mb-3">
        <div class="col-12">
            <a href="{{route('agent.register_member')}}" class="btn btn-success">
                <i class="fas fa-user-plus mr-1"></i> Register New Member
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead class="thead-dark">
                        <tr>
                            <th>Date</th>
                            <th>Member Name</th>
                            <th>Phone</th>
                            <th class="text-right">Registration Fee</th>
                            <th class="text-right">Commission</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($registrations as $reg)
                        <tr>
                            <td><small>{{$reg->created_at->format('d M Y')}}</small></td>
                            <td>
                                @if($reg->client)
                                <strong>{{$reg->client->first_name}} {{$reg->client->last_name}}</strong>
                                @else
                                <span class="text-muted">N/A</span>
                                @endif
                            </td>
                            <td>{{$reg->client->mobile ?? 'N/A'}}</td>
                            <td class="text-right">{{number_format($reg->registration_fee, 2)}}</td>
                            <td class="text-right">
                                @if($reg->status == 'approved')
                                <span class="text-success">{{number_format($reg->commission_earned, 2)}}</span>
                                @else
                                <span class="text-muted">{{number_format($reg->commission_earned, 2)}} (pending)</span>
                                @endif
                            </td>
                            <td>{!! $reg->status_badge !!}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">
                                <i class="fas fa-users fa-3x mb-2"></i><br>
                                No member registrations yet
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($registrations->hasPages())
        <div class="card-footer">
            {{$registrations->links()}}
        </div>
        @endif
    </div>
</section>

@endsection
