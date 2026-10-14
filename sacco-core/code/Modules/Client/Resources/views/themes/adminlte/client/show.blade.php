@extends('core::layouts.master')
@section('title')
    {{ $client->first_name }} {{ $client->last_name }}
@endsection
@section('styles')
@stop
@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>
                        {{ $client->first_name }} {{ $client->last_name }}
                        <a href="#" onclick="window.history.back()"
                           class="btn btn-outline-light bg-white d-none d-sm-inline-flex">
                            <em class="icon ni ni-arrow-left"></em><span>{{ trans_choice('core::general.back',1) }}</span>
                        </a>
                    </h1>

                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a
                                    href="{{url('dashboard')}}">{{ trans_choice('dashboard::general.dashboard',1) }}</a>
                        </li>
                        <li class="breadcrumb-item"><a
                                    href="{{url('client')}}">{{ trans_choice('client::general.client',2) }}</a>
                        </li>
                        <li class="breadcrumb-item active">{{ $client->first_name }} {{ $client->last_name }}</li>
                    </ol>
                </div>
            </div>
        </div><!-- /.container-fluid -->
    </section>
    <section class="content" id="app">
        <div class="row">
            <div class="col-md-3">
                <div class="card card-bordered card-preview">
                    <div class="card-body box-profile">

                        <div class="text-center">
                            @if(!empty($client->photo))
                                <a href="{{asset('storage/uploads/clients/'.$client->photo)}}"
                                   class="fancybox">
                                    <img
                                            class="profile-user-img img-fluid img-circle"
                                            src="{{asset('storage/uploads/clients/'.$client->photo)}}"
                                            alt="User profile picture">
                                </a>
                            @else
                                <img class="profile-user-img img-fluid img-circle"
                                     src="{{asset('themes/adminlte/img/user.png')}}"
                                     alt="User profile picture">
                            @endif
                        </div>
                        <h3 class="profile-username text-center">
                            @if(!empty($client->title))
                                {{$client->title->name}}
                            @endif
                            {{$client->name}}
                        </h3>
                        @if(!empty($client->profession->name))
                            <p class="text-muted text-center">{{$client->profession->name}}</p>
                        @endif
                        <p class="text-muted text-center">#{{$client->id}}</p>
                        <ul class="list-group list-group-unbordered mb-3">
                            <li class="list-group-item">
                                <b>
                                    {{trans_choice('core::general.branch',1)}}
                                </b>
                                <a class="float-right">
                                    @if(!empty($client->branch))
                                        {{$client->branch->name}}
                                    @endif
                                </a>
                            </li>
                            <li class="list-group-item">
                                <b>
                                    {{trans_choice('core::general.status',1)}}
                                </b>
                                <a class="float-right">
                                    <a class="float-right" data-toggle="modal"
                                       data-target="#change_status_modal" href="#">
                                        @if($client->status=='pending')
                                            {{trans_choice('core::general.pending',1)}}
                                        @endif
                                        @if($client->status=='active')
                                            {{trans_choice('core::general.active',1)}}
                                        @endif
                                        @if($client->status=='inactive')
                                            {{trans_choice('core::general.inactive',1)}}
                                        @endif
                                        @if($client->status=='deceased')
                                            {{trans_choice('core::general.deceased',1)}}
                                        @endif
                                        @if($client->status=='other')
                                            {{trans_choice('core::general.other',1)}}
                                        @endif
                                        @if($client->status=='closed')
                                            {{trans_choice('core::general.closed',1)}}
                                        @endif
                                    </a>
                                </a>
                            </li>
                            <li class="list-group-item">
                                <b>
                                    {{trans('sacco::general.member_number')}}
                                </b>
                                <a class="float-right">
                                    {{$member360['member_number'] ?? $client->member_number ?? $client->external_id}}
                                </a>
                            </li>
                            <li class="list-group-item">
                                <b>
                                    {{trans_choice('core::general.external_id',1)}}
                                </b>
                                <a class="float-right">
                                    {{$client->external_id}}
                                </a>
                            </li>
                            <li class="list-group-item">
                                <b>
                                    {{trans_choice('core::general.type',1)}}
                                </b>
                                <a class="float-right">
                                    @if(!empty($client->client_type))
                                        {{$client->client_type->name}}
                                    @endif
                                </a>
                            </li>
                            <li class="list-group-item">
                                <b>
                                    {{trans_choice('core::general.staff',1)}}
                                </b>
                                <a class="float-right">
                                    @if(!empty($client->loan_officer))
                                        {{$client->loan_officer->first_name}} {{$client->loan_officer->last_name}}
                                    @endif
                                </a>
                            </li>
                            <li class="list-group-item">
                                <b>
                                    {{trans_choice('core::general.mobile',1)}}
                                </b>
                                <a class="float-right">
                                    {{$client->mobile}}
                                </a>
                            </li>
                            <li class="list-group-item">
                                <b>
                                    {{trans_choice('core::general.email',1)}}
                                </b>
                                <a class="float-right">
                                    {{$client->email}}
                                </a>
                            </li>
                            <li class="list-group-item">
                                <b>
                                    {{trans_choice('core::general.dob',1)}}
                                </b>
                                <a class="float-right">
                                    {{$client->dob}}
                                </a>
                            </li>
                            <li class="list-group-item">
                                <b>
                                    {{trans_choice('core::general.gender',1)}}
                                </b>
                                <a class="float-right">
                                    @if($client->gender=='male')
                                        {{trans_choice('core::general.male',1)}}
                                    @endif
                                    @if($client->gender=='female')
                                        {{trans_choice('core::general.female',1)}}
                                    @endif
                                    @if($client->gender=='unspecified')
                                        {{trans_choice('core::general.unspecified',1)}}
                                    @endif
                                    @if($client->gender=='other')
                                        {{trans_choice('core::general.other',1)}}
                                    @endif
                                </a>
                            </li>
                            <li class="list-group-item">
                                <b>
                                    {{trans_choice('client::general.marital_status',1)}}
                                </b>
                                <a class="float-right">
                                    @if($client->marital_status=='single')
                                        {{trans_choice('client::general.single',1)}}
                                    @endif
                                    @if($client->marital_status=='married')
                                        {{trans_choice('client::general.married',1)}}
                                    @endif
                                    @if($client->marital_status=='divorced')
                                        {{trans_choice('client::general.divorced',1)}}
                                    @endif
                                    @if($client->marital_status=='widowed')
                                        {{trans_choice('client::general.widowed',1)}}
                                    @endif
                                    @if($client->marital_status=='other')
                                        {{trans_choice('client::general.other',1)}}
                                    @endif
                                    @if($client->marital_status=='unspecified')
                                        {{trans_choice('core::general.unspecified',1)}}
                                    @endif
                                </a>
                            </li>
                            <li class="list-group-item">
                                <b>
                                    {{trans_choice('core::general.zip',1)}}
                                </b>
                                <a class="float-right">
                                    {{$client->zip}}
                                </a>
                            </li>
                            <li class="list-group-item">
                                <b>
                                    {{trans_choice('core::general.joined_date',1)}}
                                </b>
                                <a class="float-right">
                                    {{$client->created_date}}
                                </a>
                            </li>
                            @foreach($custom_fields as $custom_field)
                                <?php
                                $field = custom_field_build_form_field($custom_field, $client->id);
                                ?>
                                <li class="list-group-item">
                                    <b>
                                        {{$field['label']}}
                                    </b>
                                    <a class="float-right">
                                        @if($custom_field->type=='checkbox')
                                            @foreach(explode(',',$field['current'] ) as $key)
                                                {{$key}}<br>
                                            @endforeach
                                        @else
                                            {{$field['current'] }}
                                        @endif
                                    </a>
                                </li>
                            @endforeach
                            <li class="list-group-item">
                                <b>
                                    {{trans_choice('core::general.activation_date',1)}}
                                </b>
                                <a class="float-right">
                                    {{$client->activation_date}}
                                </a>
                            </li>
                        </ul>
                        <div class="d-flex justify-content-center">
                            @can('client.clients.activate')
                                <a href="#" data-toggle="modal" class="btn btn-primary btn-sm  m-1"
                                   data-target="#change_status_modal">
                                    <i class="fas fa-check-circle"></i>
                                    <span>{{trans_choice('client::general.change',1)}} {{trans_choice('core::general.status',1)}}</span>
                                </a>
                            @endcan
                            @can('client.clients.edit')
                                <a href="{{url('client/' . $client->id . '/edit')}}"
                                   class="btn btn-primary btn-sm  m-1">
                                    <i class="fas fa-edit"></i>
                                    <span>{{trans_choice('core::general.edit',1)}}</span>
                                </a>

                                <a href="#" data-toggle="modal"
                                   data-target="#transfer_client_modal" class="btn btn-primary btn-sm m-1"><i
                                            class="fas fa-forward"></i>
                                    <span>{{trans_choice('client::general.transfer',1)}}</span>
                                </a>
                            @endcan
                        </div>
                    </div>
                </div>
                <div class="card">
                    <!-- /.card-header -->
                    <div class="card-body">

                        <strong><i class="fas fa-map-marker-alt mr-1"></i> {{trans_choice('core::general.address',1)}}
                        </strong>

                        <p class="text-muted">
                            {{$client->address}}<br>
                            @if(!empty($client->country))
                                {{$client->country->name}}
                            @endif
                        </p>

                        <hr>

                        <strong><i class="far fa-file-alt mr-1"></i> {{trans_choice('core::general.note',2)}}</strong>

                        <p class="text-muted"> {{$client->notes}}</p>
                    </div>
                    <!-- /.card-body -->
                </div>
            </div>
            <!-- /.col -->
            <div class="col-md-9">
                @if(!empty($member360))
                <div class="row mb-3">
                    <div class="col-md-12">
                        <div class="card card-success">
                            <div class="card-header">
                                <h3 class="card-title">{{trans('sacco::general.member_360')}}</h3>
                                <div class="card-tools">
                                    <a href="{{url('client/'.$client->id.'/member360')}}" class="btn btn-sacco-accent btn-sm">
                                        <i class="fas fa-expand mr-1"></i>Full 360 View
                                    </a>
                                </div>
                                @php $kycBadge = in_array($member360['kyc_status'] ?? '', ['verified']) ? 'success' : (in_array($member360['kyc_status'] ?? '', ['pending_review','pending']) ? 'warning' : 'danger'); @endphp
                                <span class="badge badge-{{$kycBadge}} float-right mr-2">
                                    KYC: {{ucfirst(str_replace('_',' ',$member360['kyc_status'] ?? 'incomplete'))}}
                                </span>
                            </div>
                            <div class="card-body">
                                <div class="row text-center">
                                    <div class="col-md-2"><strong>{{trans('sacco::general.savings_balance')}}</strong><br>{{number_format($member360['savings_balance'],2)}}</div>
                                    <div class="col-md-2"><strong>{{trans('sacco::general.share_value')}}</strong><br>{{number_format($member360['share_value'],2)}}</div>
                                    <div class="col-md-2"><strong>{{trans('sacco::general.loan_balance')}}</strong><br>{{number_format($member360['loan_balance'],2)}}</div>
                                    <div class="col-md-2"><strong>{{trans('sacco::general.wallet_balance')}}</strong><br>{{number_format($member360['wallet_balance'],2)}}</div>
                                    <div class="col-md-2"><strong>{{trans('sacco::general.arrears')}}</strong><br>{{number_format($member360['arrears_amount'],2)}}</div>
                                    <div class="col-md-2"><strong>{{trans('sacco::general.net_position')}}</strong><br>{{number_format($member360['net_position'],2)}}</div>
                                </div>
                                @if(!empty($member360['active_earmarks']))
                                <div class="mt-2">
                                    @foreach($member360['active_earmarks'] as $earmark)
                                    <div class="alert alert-{{$earmark['severity']==='critical'?'danger':($earmark['severity']==='warning'?'warning':'info')}} py-1 mb-1">
                                        <strong>{{$earmark['title']}}</strong>: {{$earmark['message']}}
                                    </div>
                                    @endforeach
                                </div>
                                @endif
                                <div class="mt-2 text-center">
                                    @can('sacco.earmarks.create')
                                    <a href="{{url('sacco/earmark/create?client_id='.$client->id)}}" class="btn btn-sm btn-warning">Add Earmark</a>
                                    @endcan
                                    @can('sacco.transfers.create')
                                    <a href="{{url('sacco/transfer/create?client_id='.$client->id)}}" class="btn btn-sm btn-primary">BOSA/FOSA Transfer</a>
                                    @endcan
                                    @can('sacco.exit.create')
                                    <a href="{{url('sacco/exit/create?client_id='.$client->id)}}" class="btn btn-sm btn-danger">Exit Settlement</a>
                                    @endcan
                                    @can('sacco.cards.manage')
                                    <a href="{{url('sacco/cards/create?client_id='.$client->id)}}" class="btn btn-sm btn-info">Issue Debit Card</a>
                                    @endcan
                                    @can('sacco.kyc.index')
                                    <a href="{{url('sacco/kyc/'.$client->id.'/show')}}" class="btn btn-sm btn-info">Member KYC / IPRS</a>
                                    @endcan
                                    @can('sacco.integrations.index')
                                    <button type="button" class="btn btn-sm btn-secondary" data-toggle="modal" data-target="#signature_modal">
                                        {{ !empty($client->signature) ? 'Update Signature' : 'Capture Signature' }}
                                    </button>
                                    @endcan
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif
                <div class="card card-primary card-outline card-outline-tabs">
                    <div class="card-header p-0 border-bottom-0">
                        <ul class="nav nav-tabs">
                            <li class="nav-item">
                                <a class="nav-link active" href="#accounts" data-toggle="tab"
                                   aria-expanded="false">{{trans_choice('client::general.account',2)}}
                                </a>
                            </li>
                            @can('client.clients.identification.index')
                                <li class="nav-item">
                                    <a class="nav-link" href="#client_identification" data-toggle="tab"
                                       aria-expanded="false">{{trans_choice('client::general.identification',1)}}</a>
                                </li>
                            @endcan
                            @can('client.clients.next_of_kin.index')
                                <li class="nav-item">
                                    <a class="nav-link" href="#client_next_of_kin" data-toggle="tab"
                                       aria-expanded="true">{{trans_choice('client::general.next_of_kin',1)}}</a>
                                </li>
                            @endcan
                            @can('client.clients.index')
                                <li class="nav-item">
                                    <a class="nav-link" href="#login_details" data-toggle="tab"
                                       aria-expanded="false">{{trans_choice('user::general.login',1)}} {{trans_choice('core::general.detail',2)}}</a>
                                </li>
                            @endcan
                            @can('client.clients.files.index')
                                <li class="nav-item">
                                    <a class="nav-link" href="#files" data-toggle="tab"
                                       aria-expanded="false">{{trans_choice('client::general.file',2)}}</a>
                                </li>
                            @endcan
                            @can('sacco.ussd.members')
                                <li class="nav-item">
                                    <a class="nav-link" href="#ussd" data-toggle="tab" aria-expanded="false">
                                        <i class="fas fa-mobile-alt mr-1"></i> USSD Access
                                    </a>
                                </li>
                            @endcan
                        </ul>
                    </div>
                    <div class="card-body">
                        <div class="tab-content">
                            <div class="tab-pane active" id="accounts">
                                @can('loan.loans.index')
                                    <h4>{{ trans_choice('loan::general.loan',2) }}</h4>
                                    <table class="table  table-striped table-hover table-condensed"
                                           id="loan-data-table">
                                        <thead>
                                        <tr>
                                            <th>{{ trans_choice('core::general.id',1) }}</th>
                                            <th>{{ trans_choice('core::general.amount',1) }}</th>
                                            <th>{{ trans_choice('loan::general.balance',1) }}</th>
                                            <th>{{ trans('loan::general.disbursed') }}</th>
                                            <th>{{ trans_choice('loan::general.status',1) }}</th>
                                            <th>{{ trans_choice('loan::general.product',1) }}</th>
                                            <th>{{ trans_choice('core::general.action',1) }}</th>
                                        </tr>
                                        </thead>
                                        <tbody>

                                        </tbody>
                                    </table>
                                @endcan
                                @can('savings.savings.index')
                                    <h4>{{ trans_choice('savings::general.savings',2) }}</h4>
                                    <table class="table  table-striped table-hover table-condensed"
                                           id="savings-data-table">
                                        <thead>
                                        <tr>
                                            <th>{{ trans_choice('core::general.id',1) }}</th>
                                            <th>{{ trans_choice('savings::general.interest_rate',1) }}</th>
                                            <th>{{ trans_choice('savings::general.balance',1) }}</th>
                                            <th>{{ trans_choice('savings::general.status',1) }}</th>
                                            <th>{{ trans_choice('savings::general.product',1) }}</th>
                                            <th>{{ trans_choice('core::general.action',1) }}</th>
                                        </tr>
                                        </thead>
                                        <tbody>

                                        </tbody>
                                    </table>
                                @endcan
                                @can('share.shares.index')
                                    <h4>{{ trans_choice('share::general.share',2) }}</h4>
                                    <table class="table table-striped table-hover table-condensed" id="share-data-table">
                                        <thead>
                                        <tr>
                                            <th>{{ trans_choice('core::general.id',1) }}</th>
                                            <th>{{ trans_choice('share::general.total_shares',1) }}</th>
                                            <th>{{ trans_choice('sacco::general.guarantor_locked_shares',1) }}</th>
                                            <th>{{ trans_choice('share::general.status',1) }}</th>
                                            <th>{{ trans_choice('share::general.product',1) }}</th>
                                            <th>{{ trans_choice('core::general.action',1) }}</th>
                                        </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                @endcan
                                @can('wallet.wallets.index')
                                    <h4>{{ trans_choice('wallet::general.wallet',2) }}</h4>
                                    <table class="table table-striped table-hover table-condensed" id="wallet-data-table">
                                        <thead>
                                        <tr>
                                            <th>{{ trans_choice('core::general.id',1) }}</th>
                                            <th>{{ trans_choice('wallet::general.balance',1) }}</th>
                                            <th>{{ trans_choice('wallet::general.status',1) }}</th>
                                            <th>{{ trans_choice('core::general.action',1) }}</th>
                                        </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                @endcan
                            </div>
                            @can('client.clients.identification.index')
                                <div class="tab-pane" id="client_identification">
                                    <div>
                                        @can('client.clients.identification.create')
                                            <a href="{{url('client/'.$client->id.'/client_identification/create')}}"
                                               class="btn btn-info float-right mb-2">{{trans_choice('core::general.add',1)}} {{trans_choice('client::general.identification',1)}}</a>
                                        @endcan
                                        <table class="table table-striped table-bordered table-hover">
                                            <thead>
                                            <tr>
                                                <th>{{ trans_choice('core::general.type',1) }}</th>
                                                <th>{{ trans_choice('core::general.id',1) }}</th>
                                                <th>{{ trans_choice('core::general.description',1) }}</th>
                                                <th>{{ trans_choice('core::general.action',1) }}</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            @foreach($client->identifications as $key)
                                                <tr>
                                                    <td>
                                                        @if(!empty($key->identification_type))
                                                            {{$key->identification_type->name}}
                                                        @endif
                                                    </td>
                                                    <td>{{$key->identification_value}}</td>
                                                    <td>{{$key->description}}</td>
                                                    <td>
                                                        <a href="{{asset('storage/uploads/clients/'.$key->link)}}"
                                                           target="_blank"><i class="fa fa-download"></i> </a>
                                                        @can('client.clients.identification.edit')
                                                            <a href="{{url('client/client_identification/'.$key->id.'/edit')}}"><i
                                                                        class="fas fa-edit"></i> </a>
                                                        @endcan
                                                        @can('client.clients.identification.destroy')
                                                            <a href="{{url('client/client_identification/'.$key->id.'/destroy')}}"
                                                               class="confirm"><i class="fas fa-trash"></i> </a>
                                                        @endcan
                                                    </td>
                                                </tr>
                                            @endforeach
                                            </tbody>
                                        </table>
                                    </div>

                                </div>
                            @endcan
                        <!-- /.tab-pane -->
                            @can('client.clients.next_of_kin.index')
                                <div class="tab-pane" id="client_next_of_kin">
                                    @can('client.clients.next_of_kin.create')
                                        <a href="{{url('client/'.$client->id.'/client_next_of_kin/create')}}"
                                           class="btn btn-info float-right mb-2">{{trans_choice('core::general.add',1)}} {{trans_choice('client::general.next_of_kin',1)}}</a>
                                    @endcan
                                    <table class="table table-striped table-bordered table-hover">
                                        <thead>
                                        <tr>
                                            <th>{{ trans_choice('core::general.name',1) }}</th>
                                            <th>{{ trans('core::general.gender') }}</th>
                                            <th>{{ trans('core::general.dob') }}</th>
                                            <th>{{ trans('core::general.mobile') }}</th>
                                            <th>{{ trans_choice('core::general.email',1) }}</th>
                                            <th>{{ trans_choice('client::general.profession',1) }}</th>
                                            <th>{{ trans_choice('client::general.relationship',1) }}</th>
                                            <th>{{ trans_choice('core::general.address',1) }}</th>
                                            <th>{{ trans_choice('core::general.action',1) }}</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @foreach($client->next_of_kins as $key)
                                            <tr>
                                                <td>{{$key->first_name}} {{$key->last_name}}</td>
                                                <td>
                                                    @if($key->gender=='male')
                                                        {{trans_choice('core::general.male',1)}}
                                                    @endif
                                                    @if($key->gender=='female')
                                                        {{trans_choice('core::general.female',1)}}
                                                    @endif
                                                    @if($key->gender=='unspecified')
                                                        {{trans_choice('core::general.unspecified',1)}}
                                                    @endif
                                                    @if($key->gender=='other')
                                                        {{trans_choice('core::general.other',1)}}
                                                    @endif
                                                </td>
                                                <td>{{$key->dob}}</td>
                                                <td>{{$key->mobile}}</td>
                                                <td>{{$key->email}}</td>
                                                <td>
                                                    @if(!empty($key->profession))
                                                        {{$key->profession->name}}
                                                    @endif
                                                </td>
                                                <td>
                                                    @if(!empty($key->client_relationship))
                                                        {{$key->client_relationship->name}}
                                                    @endif
                                                </td>

                                                <td>{{$key->address}}</td>
                                                <td>
                                                    @can('client.clients.next_of_kin.edit')
                                                        <a href="{{url('client/client_next_of_kin/'.$key->id.'/edit')}}"><i
                                                                    class="fas fa-edit"></i> </a>
                                                    @endcan
                                                    @can('client.clients.next_of_kin.destroy')
                                                        <a href="{{url('client/client_next_of_kin/'.$key->id.'/destroy')}}"
                                                           class="confirm"><i class="fas fa-trash"></i> </a>
                                                    @endcan
                                                </td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endcan
                            <div class="tab-pane" id="login_details">
                                @can('client.clients.user.create')
                                    <a href="{{url('client/'.$client->id.'/user/create')}}"
                                       class="btn btn-info float-right mb-2">{{trans_choice('core::general.add',1)}} {{trans_choice('core::general.user',1)}}</a>
                                @endcan
                                <table class="table table-striped table-bordered table-hover">
                                    <thead>
                                    <tr>
                                        <th>{{ trans_choice('core::general.name',1) }}</th>
                                        <th>{{ trans_choice('core::general.email',1) }}</th>
                                        <th>{{ trans_choice('core::general.action',1) }}</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($client->client_users as $key)
                                        @if($key->user)
                                            <tr>
                                                <td>{{$key->user->first_name}} {{$key->user->last_name}}</td>
                                                <td>{{$key->user->email}}</td>
                                                <td>
                                                    @can('client.clients.user.create')
                                                        <a href="{{url('user/'.$key->user_id.'/edit')}}"
                                                           class=""><i class="fas fa-edit"></i> </a>
                                                    @endcan
                                                    @can('client.clients.user.destroy')
                                                        <a href="{{url('client/user/'.$key->id.'/destroy')}}"
                                                           class="confirm"><i class="fas fa-trash"></i> </a>
                                                    @endcan
                                                </td>
                                            </tr>
                                        @endif
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @can('client.clients.files.index')
                                <div class="tab-pane" id="files">
                                    <div>
                                        @can('client.clients.files.index')
                                            <a href="{{url('client/'.$client->id.'/file/create')}}"
                                               class="btn btn-info float-right mb-2">{{trans_choice('core::general.add',1)}} {{trans_choice('client::general.file',1)}}</a>
                                        @endcan
                                        <table class="table table-striped table-bordered table-hover">
                                            <thead>
                                            <tr>
                                                <th>{{ trans_choice('core::general.name',1) }}</th>
                                                <th>{{ trans_choice('core::general.description',1) }}</th>
                                                <th>{{ trans_choice('core::general.action',1) }}</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            @foreach($client->files as $key)
                                                <tr>
                                                    <td>{{$key->name}}</td>
                                                    <td>{{$key->description}}</td>
                                                    <td>
                                                        <a href="{{asset('storage/uploads/clients/'.$key->link)}}"
                                                           target="_blank"><i class="fa fa-download"></i> </a>
                                                        @can('client.clients.files.edit')
                                                            <a href="{{url('client/file/'.$key->id.'/edit')}}"><i
                                                                        class="fas fa-edit"></i> </a>
                                                        @endcan
                                                        @can('client.clients.files.destroy')
                                                            <a href="{{url('client/file/'.$key->id.'/destroy')}}"
                                                               class="confirm"><i class="fas fa-trash"></i> </a>
                                                        @endcan
                                                    </td>
                                                </tr>
                                            @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            @endcan
                        </div>
                    </div>

                </div>
            </div>
            <!-- /.col -->
        </div>
        <div class="modal fade in" id="change_status_modal">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">{{ trans_choice('client::general.change',1) }} {{ trans_choice('core::general.status',1) }}</h4>
                        <button type="button" class="close" data-dismiss="modal">
                            <span>×</span></button>
                    </div>
                    <form method="post"
                          action="{{ url('client/'.$client->id.'/change_status') }}"
                          class="form-horizontal">
                        {{csrf_field()}}
                        <div class="modal-body">
                            <div class="form-group">
                                <label for="status"
                                       class="control-label">{{ trans_choice('core::general.status',1) }}</label>
                                <select class="form-control" name="status" id="status">
                                    <option value=""></option>
                                    <option value="pending"
                                            @if($client->status=="pending") selected @endif>{{trans_choice("client::general.pending",1)}}</option>
                                    <option value="active"
                                            @if($client->status=="active") selected @endif>{{trans_choice("client::general.active",1)}}</option>
                                    <option value="inactive"
                                            @if($client->status=="inactive") selected @endif>{{trans_choice("client::general.inactive",1)}}</option>
                                    <option value="closed"
                                            @if($client->status=="closed") selected @endif>{{trans_choice("client::general.closed",1)}}</option>
                                    <option value="deceased"
                                            @if($client->status=="deceased") selected @endif>{{trans_choice("client::general.deceased",1)}}</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="member_status_detail" class="control-label">Member Status Detail (SACCO)</label>
                                <select class="form-control" name="member_status_detail" id="member_status_detail">
                                    <option value="">—</option>
                                    @foreach(['active','dormant','defaulting','exit','pending','inactive','closed','deceased'] as $detail)
                                    <option value="{{$detail}}" @if(($client->member_status_detail ?? '')==$detail) selected @endif>{{ucfirst($detail)}}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="kyc_verified" class="control-label">KYC Verified</label>
                                <select class="form-control" name="kyc_verified" id="kyc_verified">
                                    <option value="0" @if(empty($client->kyc_verified)) selected @endif>{{trans_choice('core::general.no',1)}}</option>
                                    <option value="1" @if(!empty($client->kyc_verified)) selected @endif>{{trans_choice('core::general.yes',1)}}</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="status_date"
                                       class="control-label">{{ trans_choice('core::general.date',1) }}</label>
                                <input type="text" name="date"
                                       class="form-control date-picker"
                                       value="{{date("Y-m-d")}}"
                                       required=""
                                       id="status_date">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-default float-left"
                                    data-dismiss="modal">
                                {{ trans_choice('core::general.close',1) }}
                            </button>
                            <button type="submit"
                                    class="btn btn-primary float-right">{{ trans_choice('core::general.save',1) }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        @can('sacco.integrations.index')
        <div class="modal fade" id="signature_modal">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">E-Signature Capture</h4>
                        <button type="button" class="close" data-dismiss="modal"><span>×</span></button>
                    </div>
                    <form method="post" action="{{ url('sacco/integration/signature') }}" id="signature_form">
                        {{ csrf_field() }}
                        <input type="hidden" name="client_id" value="{{ $client->id }}">
                        <input type="hidden" name="signature" id="signature_data">
                        <div class="modal-body">
                            <p class="text-muted">Sign in the box below using mouse or touch.</p>
                            <canvas id="signature_canvas" width="600" height="200" style="border:1px solid #ccc;width:100%;touch-action:none;"></canvas>
                            <button type="button" class="btn btn-sm btn-default mt-2" id="clear_signature">Clear</button>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-default" data-dismiss="modal">{{ trans_choice('core::general.close',1) }}</button>
                            <button type="submit" class="btn btn-primary">{{ trans_choice('core::general.save',1) }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        @endcan
    </section>

    {{-- ── USSD Access Section ────────────────────────────────────────────── --}}
    @can('sacco.ussd.members')
    <section class="content" id="ussd">
        <div class="container-fluid">
            @php $allServices = \Modules\Sacco\Entities\UssdMemberConfig::ALL_SERVICES ?? []; @endphp

            <div class="callout callout-{{ ($ussdConfig && $ussdConfig->isLocked()) ? 'danger' : (($ussdConfig && $ussdConfig->ussd_enabled) ? 'success' : 'default') }}">
                <h5>
                    <i class="fas fa-mobile-alt mr-1"></i> USSD Mobile Banking
                    — Shortcode: <strong>{{ \Modules\Setting\Entities\Setting::where('setting_key','ussd.shortcode')->value('setting_value') ?? config('sacco.ussd_shortcode','*123#') }}</strong>
                </h5>
                <div class="row">
                    @if($ussdConfig && $ussdConfig->ussd_enabled)
                        <div class="col-md-3"><strong>Status:</strong>
                            @if($ussdConfig->isLocked())
                                <span class="badge badge-danger">Locked</span>
                            @else
                                <span class="badge badge-success">Enabled</span>
                            @endif
                        </div>
                        <div class="col-md-3"><strong>PIN Set:</strong>
                            {{ $ussdConfig->pin_set ? 'Yes' : 'Not Set' }}
                        </div>
                        <div class="col-md-3"><strong>Failed Attempts:</strong>
                            <span class="{{ $ussdConfig->failed_pin_attempts >= 2 ? 'text-warning' : '' }}">
                                {{ $ussdConfig->failed_pin_attempts }}
                            </span>
                        </div>
                        <div class="col-md-3"><strong>Last Access:</strong>
                            {{ $ussdConfig->last_access_at ? $ussdConfig->last_access_at->diffForHumans() : 'Never' }}
                        </div>
                    @else
                        <div class="col-12 text-muted">USSD is not active for this member.</div>
                    @endif
                </div>
            </div>

            <div class="row">

                {{-- Enable / Disable --}}
                <div class="col-md-4">
                    <div class="card card-outline card-{{ ($ussdConfig && $ussdConfig->ussd_enabled) ? 'success' : 'secondary' }}">
                        <div class="card-header"><h3 class="card-title">Access Control</h3></div>
                        <div class="card-body">
                            @if($ussdConfig && $ussdConfig->ussd_enabled)
                                <form method="post" action="{{ url('sacco/ussd/member/'.$client->id.'/disable') }}">
                                    @csrf
                                    <p class="text-muted small">USSD is currently <strong class="text-success">active</strong>.</p>
                                    <button class="btn btn-warning btn-sm confirm" data-confirm="Disable USSD for {{ $client->first_name }}?">
                                        <i class="fas fa-ban mr-1"></i> Disable USSD
                                    </button>
                                </form>
                            @else
                                <form method="post" action="{{ url('sacco/ussd/member/'.$client->id.'/enable') }}">
                                    @csrf
                                    <p class="text-muted small">USSD is currently <strong>not active</strong>.</p>
                                    <div class="form-group">
                                        <input type="text" name="notes" class="form-control form-control-sm"
                                               placeholder="Notes (optional)">
                                    </div>
                                    <button class="btn btn-success btn-sm">
                                        <i class="fas fa-check mr-1"></i> Enable USSD
                                    </button>
                                </form>
                            @endif

                            @if($ussdConfig && $ussdConfig->isLocked())
                                <hr>
                                <form method="post" action="{{ url('sacco/ussd/member/'.$client->id.'/unlock') }}">
                                    @csrf
                                    <p class="text-danger small">Account locked after too many failed attempts.</p>
                                    <button class="btn btn-info btn-sm">
                                        <i class="fas fa-unlock mr-1"></i> Unlock Account
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- PIN Management --}}
                <div class="col-md-4">
                    <div class="card card-outline card-info">
                        <div class="card-header"><h3 class="card-title">PIN Management</h3></div>
                        <div class="card-body">
                            <form method="post" action="{{ url('sacco/ussd/member/'.$client->id.'/reset-pin') }}"
                                  class="confirm mb-3" data-confirm="Generate a new temporary PIN for this member?">
                                @csrf
                                <p class="text-muted small">Auto-generate a temp PIN and SMS it to the member.</p>
                                <button class="btn btn-sm btn-secondary">
                                    <i class="fas fa-redo mr-1"></i> Reset PIN via SMS
                                </button>
                            </form>
                            <hr>
                            <form method="post" action="{{ url('sacco/ussd/member/'.$client->id.'/set-pin') }}">
                                @csrf
                                <p class="text-muted small">Set a specific PIN (admin override).</p>
                                <div class="form-group mb-2">
                                    <input type="password" name="pin" class="form-control form-control-sm"
                                           placeholder="4-digit PIN" maxlength="4" pattern="\d{4}" required>
                                </div>
                                <div class="form-group mb-2">
                                    <input type="password" name="pin_confirmation" class="form-control form-control-sm"
                                           placeholder="Confirm PIN" maxlength="4" pattern="\d{4}" required>
                                </div>
                                <button class="btn btn-sm btn-primary">
                                    <i class="fas fa-key mr-1"></i> Set PIN
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                {{-- Services & Limits --}}
                <div class="col-md-4">
                    <div class="card card-outline card-primary">
                        <div class="card-header"><h3 class="card-title">Services & Limits</h3></div>
                        <div class="card-body">
                            <form method="post" action="{{ url('sacco/ussd/member/'.$client->id.'/set-services') }}">
                                @csrf
                                @php $enabledServices = $ussdConfig->allowed_services ?? array_keys($allServices); @endphp
                                <div class="mb-2">
                                    @foreach($allServices as $key => $label)
                                        <div class="custom-control custom-checkbox mb-1">
                                            <input type="checkbox" class="custom-control-input"
                                                   id="adm_svc_{{ $key }}" name="allowed_services[]"
                                                   value="{{ $key }}" {{ in_array($key, $enabledServices) ? 'checked' : '' }}>
                                            <label class="custom-control-label" for="adm_svc_{{ $key }}">{{ $label }}</label>
                                        </div>
                                    @endforeach
                                </div>
                                <div class="form-group mb-2">
                                    <label class="small">Daily Limit (blank = global default)</label>
                                    <input type="number" name="daily_transaction_limit" class="form-control form-control-sm"
                                           value="{{ $ussdConfig->daily_transaction_limit ?? '' }}" min="0" step="100">
                                </div>
                                <button class="btn btn-sm btn-primary">
                                    <i class="fas fa-save mr-1"></i> Save
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Recent sessions --}}
            @if($ussdSessions->isNotEmpty())
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Recent USSD Sessions</h3>
                    <div class="card-tools">
                        <a href="{{ url('sacco/ussd/sessions?s='.$client->mobile) }}" class="btn btn-sm btn-default">
                            View All
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <thead><tr>
                            <th>Date</th><th>Session ID</th><th>Last Menu</th><th>Status</th><th>Duration</th>
                        </tr></thead>
                        <tbody>
                            @foreach($ussdSessions as $s)
                            <tr>
                                <td class="text-muted small">{{ $s->created_at->format('d M y H:i') }}</td>
                                <td><code class="small">{{ Str::limit($s->session_id, 16) }}</code></td>
                                <td>{{ $s->last_menu ?? '—' }}</td>
                                <td>
                                    <span class="badge badge-{{
                                        $s->status === 'completed' ? 'success' :
                                        ($s->status === 'error' ? 'danger' :
                                        ($s->status === 'timeout' ? 'warning' : 'info'))
                                    }}">{{ $s->status }}</span>
                                </td>
                                <td class="text-muted small">
                                    @if($s->started_at && $s->ended_at)
                                        {{ $s->started_at->diffInSeconds($s->ended_at) }}s
                                    @else —
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

        </div>
    </section>
    @endcan

@endsection
@section('scripts')

    <script>
        $(document).ready(function () {


            $('#savings-data-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: '{!! url('savings/get_savings?client_id='.$client->id) !!}',
                columns: [
                    {data: 'id', name: 'id'},
                    {data: 'interest_rate', name: 'interest_rate'},
                    {data: 'balance', name: 'balance'},
                    {data: 'status', name: 'status'},
                    {data: 'savings_product', name: 'savings_products.name'},
                    {data: 'action', name: 'action', orderable: false, searchable: false}
                ],
                "order": [[0, "desc"]],
                "language": {
                    "lengthMenu": "{{ trans('general.lengthMenu') }}",
                    "zeroRecords": "{{ trans('general.zeroRecords') }}",
                    "info": "{{ trans('general.info') }}",
                    "infoEmpty": "{{ trans('general.infoEmpty') }}",
                    "search": "{{ trans('general.search') }}",
                    "infoFiltered": "{{ trans('general.infoFiltered') }}",
                    "paginate": {
                        "first": "{{ trans('general.first') }}",
                        "last": "{{ trans('general.last') }}",
                        "next": "{{ trans('general.next') }}",
                        "previous": "{{ trans('general.previous') }}"
                    }
                },
                responsive: false,
                "autoWidth": false,
                "drawCallback": function (settings) {
                    $('.confirm').on('click', function (e) {
                        e.preventDefault();
                        var href = $(this).attr('href');
                        swal({
                            title: 'Are you sure?',
                            text: '',
                            type: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#3085d6',
                            cancelButtonColor: '#d33',
                            confirmButtonText: 'Ok',
                            cancelButtonText: 'Cancel'
                        }).then(function () {
                            window.location = href;
                        })
                    });
                }
            });
            $('#loan-data-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: '{!! url('loan/get_loans?client_id='.$client->id) !!}',
                columns: [
                    {data: 'id', name: 'id'},
                    {data: 'principal', name: 'principal'},
                    {data: 'balance', name: 'balance', orderable: false},
                    {data: 'disbursed_on_date', name: 'disbursed_on_date'},
                    {data: 'status', name: 'status'},
                    {data: 'loan_product', name: 'loan_products.name'},
                    {data: 'action', name: 'action', orderable: false, searchable: false}
                ],
                "order": [[0, "desc"]],
                "language": {
                    "lengthMenu": "{{ trans('general.lengthMenu') }}",
                    "zeroRecords": "{{ trans('general.zeroRecords') }}",
                    "info": "{{ trans('general.info') }}",
                    "infoEmpty": "{{ trans('general.infoEmpty') }}",
                    "search": "{{ trans('general.search') }}",
                    "infoFiltered": "{{ trans('general.infoFiltered') }}",
                    "paginate": {
                        "first": "{{ trans('general.first') }}",
                        "last": "{{ trans('general.last') }}",
                        "next": "{{ trans('general.next') }}",
                        "previous": "{{ trans('general.previous') }}"
                    }
                },
                responsive: false,
                "drawCallback": function (settings) {
                    $('.confirm').on('click', function (e) {
                        e.preventDefault();
                        var href = $(this).attr('href');
                        swal({
                            title: 'Are you sure?',
                            text: '',
                            type: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#3085d6',
                            cancelButtonColor: '#d33',
                            confirmButtonText: 'Ok',
                            cancelButtonText: 'Cancel'
                        }).then(function () {
                            window.location = href;
                        })
                    });
                }
            });
            @can('share.shares.index')
            if ($('#share-data-table').length) {
                $('#share-data-table').DataTable({
                    processing: true, serverSide: true,
                    ajax: '{!! url('share/get_shares?client_id='.$client->id) !!}',
                    columns: [
                        {data: 'id', name: 'id'},
                        {data: 'total_shares', name: 'total_shares'},
                        {data: 'locked_shares', name: 'locked_shares', defaultContent: '0'},
                        {data: 'status', name: 'status'},
                        {data: 'share_product', name: 'share_products.name'},
                        {data: 'action', name: 'action', orderable: false, searchable: false}
                    ],
                    order: [[0, "desc"]], responsive: false, autoWidth: false
                });
            }
            @endcan
            @can('wallet.wallets.index')
            if ($('#wallet-data-table').length) {
                $('#wallet-data-table').DataTable({
                    processing: true, serverSide: true,
                    ajax: '{!! url('wallet/get_wallets?client_id='.$client->id) !!}',
                    columns: [
                        {data: 'id', name: 'id'},
                        {data: 'balance', name: 'balance'},
                        {data: 'status', name: 'status'},
                        {data: 'action', name: 'action', orderable: false, searchable: false}
                    ],
                    order: [[0, "desc"]], responsive: false, autoWidth: false
                });
            }
            @endcan
            @can('sacco.integrations.index')
            (function () {
                var canvas = document.getElementById('signature_canvas');
                if (!canvas) return;
                var ctx = canvas.getContext('2d');
                var drawing = false;
                ctx.strokeStyle = '#000';
                ctx.lineWidth = 2;
                function pos(e) {
                    var rect = canvas.getBoundingClientRect();
                    var scaleX = canvas.width / rect.width;
                    var scaleY = canvas.height / rect.height;
                    var clientX = e.touches ? e.touches[0].clientX : e.clientX;
                    var clientY = e.touches ? e.touches[0].clientY : e.clientY;
                    return {x: (clientX - rect.left) * scaleX, y: (clientY - rect.top) * scaleY};
                }
                function start(e) { drawing = true; var p = pos(e); ctx.beginPath(); ctx.moveTo(p.x, p.y); e.preventDefault(); }
                function move(e) { if (!drawing) return; var p = pos(e); ctx.lineTo(p.x, p.y); ctx.stroke(); e.preventDefault(); }
                function stop() { drawing = false; }
                canvas.addEventListener('mousedown', start);
                canvas.addEventListener('mousemove', move);
                canvas.addEventListener('mouseup', stop);
                canvas.addEventListener('mouseleave', stop);
                canvas.addEventListener('touchstart', start, {passive: false});
                canvas.addEventListener('touchmove', move, {passive: false});
                canvas.addEventListener('touchend', stop);
                $('#clear_signature').on('click', function () { ctx.clearRect(0, 0, canvas.width, canvas.height); });
                $('#signature_form').on('submit', function () {
                    $('#signature_data').val(canvas.toDataURL('image/png'));
                });
            })();
            @endcan
        })
    </script>

@endsection
