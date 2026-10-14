@extends('core::layouts.master')
@section('title')
    Member Onboarding — {{ $client->first_name }} {{ $client->last_name }}
@endsection
@section('content')
    <div class="nk-block-head nk-block-head-sm mb-4">
        <div class="nk-block-between">
            <div class="nk-block-head-content">
                <h3 class="nk-block-title page-title">Member Onboarding</h3>
                <div class="nk-block-des text-soft">
                    <p>Complete each section below to finish setting up <strong>{{ $client->first_name }} {{ $client->last_name }}</strong>.</p>
                </div>
            </div>
            <div class="nk-block-head-content">
                <a href="{{ url('client/'.$client->id.'/show') }}" class="btn btn-outline-light bg-white d-none d-sm-inline-flex">
                    <em class="icon ni ni-eye"></em><span>View Profile</span>
                </a>
            </div>
        </div>
    </div>

    {{-- Success banner --}}
    <div class="nk-block mb-4">
        <div class="alert alert-success d-flex align-items-center">
            <em class="icon ni ni-check-circle-cut fs-22px mr-3"></em>
            <div>
                <strong>Member created successfully!</strong>
                <span class="d-block text-soft">Complete the steps below to finish onboarding.</span>
            </div>
        </div>
    </div>

    {{-- Progress bar --}}
    <div class="nk-block mb-4">
        <div class="card card-bordered">
            <div class="card-inner">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-soft fs-13px">Onboarding Progress</span>
                    <span class="badge badge-{{ $progress['percent'] >= 100 ? 'success' : 'warning' }}">
                        {{ $progress['completed'] }} / {{ $progress['total'] }} steps done
                    </span>
                </div>
                <div class="progress" style="height: 8px;">
                    <div class="progress-bar bg-{{ $progress['percent'] >= 100 ? 'success' : 'primary' }}"
                         style="width: {{ $progress['percent'] }}%"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Checklist --}}
    <div class="nk-block">
        <div class="row gy-4">

            {{-- Step 1: Basic Info (done) --}}
            <div class="col-md-6">
                <div class="card card-bordered h-100">
                    <div class="card-inner">
                        <div class="d-flex align-items-start">
                            <div class="nk-iv-wg3-icon bg-success-dim mr-3" style="min-width:48px;height:48px;border-radius:50%;display:flex;align-items:center;justify-content:center;">
                                <em class="icon ni ni-check-thick text-success fs-18px"></em>
                            </div>
                            <div>
                                <h6 class="mb-1">Basic Information</h6>
                                <p class="text-soft mb-2">Name, gender, date of birth, contact details.</p>
                                <span class="badge badge-success">Completed</span>
                            </div>
                        </div>
                        <div class="mt-3">
                            <a href="{{ url('client/'.$client->id.'/edit') }}" class="btn btn-sm btn-outline-secondary">
                                <em class="icon ni ni-edit"></em> Edit
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Step 2: Identification --}}
            <div class="col-md-6">
                <div class="card card-bordered h-100">
                    <div class="card-inner">
                        <div class="d-flex align-items-start">
                            <div class="nk-iv-wg3-icon {{ $hasIdentification ? 'bg-success-dim' : 'bg-warning-dim' }} mr-3" style="min-width:48px;height:48px;border-radius:50%;display:flex;align-items:center;justify-content:center;">
                                <em class="icon ni ni-card-view {{ $hasIdentification ? 'text-success' : 'text-warning' }} fs-18px"></em>
                            </div>
                            <div>
                                <h6 class="mb-1">Identification Documents</h6>
                                <p class="text-soft mb-2">National ID, passport, or other government-issued ID.</p>
                                @if($hasIdentification)
                                    <span class="badge badge-success">{{ $client->identifications->count() }} document(s) added</span>
                                @else
                                    <span class="badge badge-warning">Pending</span>
                                @endif
                            </div>
                        </div>
                        <div class="mt-3">
                            <a href="{{ url('client/'.$client->id.'/client_identification/create') }}"
                               class="btn btn-sm {{ $hasIdentification ? 'btn-outline-secondary' : 'btn-primary' }}">
                                <em class="icon ni ni-plus"></em> {{ $hasIdentification ? 'Add More' : 'Add ID Document' }}
                            </a>
                            @if($hasIdentification)
                                <a href="{{ url('client/'.$client->id.'/client_identification/show') }}" class="btn btn-sm btn-outline-secondary ml-1">
                                    <em class="icon ni ni-eye"></em> View
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Step 3: Next of Kin --}}
            <div class="col-md-6">
                <div class="card card-bordered h-100">
                    <div class="card-inner">
                        <div class="d-flex align-items-start">
                            <div class="nk-iv-wg3-icon {{ $hasNextOfKin ? 'bg-success-dim' : 'bg-warning-dim' }} mr-3" style="min-width:48px;height:48px;border-radius:50%;display:flex;align-items:center;justify-content:center;">
                                <em class="icon ni ni-user-list {{ $hasNextOfKin ? 'text-success' : 'text-warning' }} fs-18px"></em>
                            </div>
                            <div>
                                <h6 class="mb-1">Next of Kin</h6>
                                <p class="text-soft mb-2">Emergency contact and beneficiary details.</p>
                                @if($hasNextOfKin)
                                    <span class="badge badge-success">{{ $client->next_of_kins->count() }} contact(s) added</span>
                                @else
                                    <span class="badge badge-warning">Pending</span>
                                @endif
                            </div>
                        </div>
                        <div class="mt-3">
                            <a href="{{ url('client/'.$client->id.'/client_next_of_kin/create') }}"
                               class="btn btn-sm {{ $hasNextOfKin ? 'btn-outline-secondary' : 'btn-primary' }}">
                                <em class="icon ni ni-plus"></em> {{ $hasNextOfKin ? 'Add More' : 'Add Next of Kin' }}
                            </a>
                            @if($hasNextOfKin)
                                <a href="{{ url('client/'.$client->id.'/client_next_of_kin/show') }}" class="btn btn-sm btn-outline-secondary ml-1">
                                    <em class="icon ni ni-eye"></em> View
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Step 4: Portal Access --}}
            <div class="col-md-6">
                <div class="card card-bordered h-100">
                    <div class="card-inner">
                        <div class="d-flex align-items-start">
                            <div class="nk-iv-wg3-icon {{ $hasPortalAccess ? 'bg-success-dim' : 'bg-lighter' }} mr-3" style="min-width:48px;height:48px;border-radius:50%;display:flex;align-items:center;justify-content:center;">
                                <em class="icon ni ni-shield-check {{ $hasPortalAccess ? 'text-success' : 'text-muted' }} fs-18px"></em>
                            </div>
                            <div>
                                <h6 class="mb-1">Portal Access</h6>
                                <p class="text-soft mb-2">Create a login account for the member portal.</p>
                                @if($hasPortalAccess)
                                    <span class="badge badge-success">Account created</span>
                                @else
                                    <span class="badge badge-light">Optional</span>
                                @endif
                            </div>
                        </div>
                        <div class="mt-3">
                            @if(!$hasPortalAccess)
                                @can('client.clients.user.create')
                                    <a href="{{ url('client/'.$client->id.'/user/create') }}" class="btn btn-sm btn-outline-secondary">
                                        <em class="icon ni ni-plus"></em> Create Portal Account
                                    </a>
                                @endcan
                            @else
                                <span class="text-success"><em class="icon ni ni-check"></em> Done</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

        </div>

        {{-- Footer actions --}}
        <div class="mt-4 d-flex justify-content-between">
            <a href="{{ url('client/create') }}" class="btn btn-outline-secondary">
                <em class="icon ni ni-plus"></em> Add Another Member
            </a>
            <a href="{{ url('client/'.$client->id.'/show') }}" class="btn btn-primary">
                Finish & View Profile <em class="icon ni ni-arrow-right ml-1"></em>
            </a>
        </div>
    </div>
@endsection
