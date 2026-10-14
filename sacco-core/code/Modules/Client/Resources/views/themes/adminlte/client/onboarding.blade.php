@extends('core::layouts.master')
@section('title')
    Member Onboarding — {{ $client->first_name }} {{ $client->last_name }}
@endsection
@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Member Onboarding</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ url('client') }}">{{ trans_choice('client::general.client',2) }}</a></li>
                        <li class="breadcrumb-item active">Onboarding</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>
    <section class="content">
        <div class="container-fluid">

            <div class="callout callout-success">
                <h5><i class="fas fa-check-circle"></i> Member Created Successfully!</h5>
                <p>Complete the steps below to finish onboarding <strong>{{ $client->first_name }} {{ $client->last_name }}</strong>.</p>
            </div>

            {{-- Progress bar --}}
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Onboarding Progress</span>
                        <span class="badge badge-{{ $progress['percent'] >= 100 ? 'success' : 'warning' }}">
                            {{ $progress['completed'] }} / {{ $progress['total'] }} steps
                        </span>
                    </div>
                    <div class="progress">
                        <div class="progress-bar bg-{{ $progress['percent'] >= 100 ? 'success' : 'primary' }}"
                             style="width: {{ $progress['percent'] }}%">{{ $progress['percent'] }}%</div>
                    </div>
                </div>
            </div>

            <div class="row">

                {{-- Basic Info: done --}}
                <div class="col-md-6">
                    <div class="card card-success card-outline h-100">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-check-circle text-success mr-1"></i> Basic Information</h3>
                            <div class="card-tools"><span class="badge badge-success">Completed</span></div>
                        </div>
                        <div class="card-body">
                            <p class="text-muted">Name, gender, date of birth, contact details.</p>
                            <a href="{{ url('client/'.$client->id.'/edit') }}" class="btn btn-sm btn-default">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                        </div>
                    </div>
                </div>

                {{-- Identification --}}
                <div class="col-md-6">
                    <div class="card {{ $hasIdentification ? 'card-success card-outline' : 'card-warning card-outline' }} h-100">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas {{ $hasIdentification ? 'fa-id-card text-success' : 'fa-id-card text-warning' }} mr-1"></i>
                                Identification Documents
                            </h3>
                            <div class="card-tools">
                                @if($hasIdentification)
                                    <span class="badge badge-success">{{ $client->identifications->count() }} added</span>
                                @else
                                    <span class="badge badge-warning">Pending</span>
                                @endif
                            </div>
                        </div>
                        <div class="card-body">
                            <p class="text-muted">National ID, passport, or other government-issued ID.</p>
                            <a href="{{ url('client/'.$client->id.'/client_identification/create') }}"
                               class="btn btn-sm {{ $hasIdentification ? 'btn-default' : 'btn-primary' }}">
                                <i class="fas fa-plus"></i> {{ $hasIdentification ? 'Add More' : 'Add ID Document' }}
                            </a>
                            @if($hasIdentification)
                                <a href="{{ url('client/'.$client->id.'/client_identification/show') }}" class="btn btn-sm btn-default ml-1">
                                    <i class="fas fa-eye"></i> View
                                </a>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Next of Kin --}}
                <div class="col-md-6 mt-3">
                    <div class="card {{ $hasNextOfKin ? 'card-success card-outline' : 'card-warning card-outline' }} h-100">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas {{ $hasNextOfKin ? 'fa-users text-success' : 'fa-users text-warning' }} mr-1"></i>
                                Next of Kin
                            </h3>
                            <div class="card-tools">
                                @if($hasNextOfKin)
                                    <span class="badge badge-success">{{ $client->next_of_kins->count() }} added</span>
                                @else
                                    <span class="badge badge-warning">Pending</span>
                                @endif
                            </div>
                        </div>
                        <div class="card-body">
                            <p class="text-muted">Emergency contact and beneficiary details.</p>
                            <a href="{{ url('client/'.$client->id.'/client_next_of_kin/create') }}"
                               class="btn btn-sm {{ $hasNextOfKin ? 'btn-default' : 'btn-primary' }}">
                                <i class="fas fa-plus"></i> {{ $hasNextOfKin ? 'Add More' : 'Add Next of Kin' }}
                            </a>
                            @if($hasNextOfKin)
                                <a href="{{ url('client/'.$client->id.'/client_next_of_kin/show') }}" class="btn btn-sm btn-default ml-1">
                                    <i class="fas fa-eye"></i> View
                                </a>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Portal Access --}}
                <div class="col-md-6 mt-3">
                    <div class="card {{ $hasPortalAccess ? 'card-success card-outline' : 'card-secondary card-outline' }} h-100">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-shield-alt {{ $hasPortalAccess ? 'text-success' : 'text-muted' }} mr-1"></i>
                                Portal Access
                            </h3>
                            <div class="card-tools">
                                @if($hasPortalAccess)
                                    <span class="badge badge-success">Account created</span>
                                @else
                                    <span class="badge badge-secondary">Optional</span>
                                @endif
                            </div>
                        </div>
                        <div class="card-body">
                            <p class="text-muted">Create a login account for the member portal.</p>
                            @if(!$hasPortalAccess)
                                @can('client.clients.user.create')
                                    <a href="{{ url('client/'.$client->id.'/user/create') }}" class="btn btn-sm btn-default">
                                        <i class="fas fa-user-plus"></i> Create Portal Account
                                    </a>
                                @endcan
                            @else
                                <span class="text-success"><i class="fas fa-check"></i> Done</span>
                            @endif
                        </div>
                    </div>
                </div>

            </div>

            <div class="mt-4 d-flex justify-content-between">
                <a href="{{ url('client/create') }}" class="btn btn-default">
                    <i class="fas fa-plus"></i> Add Another Member
                </a>
                <a href="{{ url('client/'.$client->id.'/show') }}" class="btn btn-primary">
                    Finish & View Profile <i class="fas fa-arrow-right ml-1"></i>
                </a>
            </div>

        </div>
    </section>
@endsection
