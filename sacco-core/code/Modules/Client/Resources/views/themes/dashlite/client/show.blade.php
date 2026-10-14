@extends('core::layouts.master')
@section('title')
    {{ $client->first_name }} {{ $client->last_name }}
@endsection
@section('styles')
@stop
@section('content')
    <div class="row">
        <div class="col-md-3">
            <div class="nk-block nk-block-lg">
                <div class="card card-bordered card-preview">
                    <div class="card-inner">
                        <div class="user-card">
                            <div class="user-avatar bg-primary">
                                @if(!empty($client->photo))
                                    <a href="{{asset('storage/uploads/clients/'.$client->photo)}}"
                                       class="fancybox">
                                        <img
                                                class="profile-user-img img-responsive img-circle"
                                                src="{{asset('storage/uploads/clients/'.$client->photo)}}"
                                                alt="User profile picture">
                                    </a>
                                @else
                                    <em class="icon ni ni-user-alt"></em>
                                @endif
                            </div>
                            <div class="user-info">
                                                <span class="lead-text">
                                                    @if(!empty($client->title))
                                                        {{$client->title->name}}
                                                    @endif
                                                    {{$client->first_name}} {{$client->last_name}}
                                                </span>
                                @if(!empty($client->profession->name))
                                    <span class="sub-text">{{$client->profession->name}}</span>
                                @endif
                                <span class="sub-text">#{{$client->id}}</span>
                            </div>
                            <div class="user-action">
                                <div class="dropdown">
                                    <a class="btn btn-icon btn-trigger mr-n2" data-toggle="dropdown"
                                       href="#" aria-expanded="false"><em
                                                class="icon ni ni-more-v"></em></a>
                                    <div class="dropdown-menu dropdown-menu-right" style="">
                                        <ul class="link-list-opt no-bdr">
                                            @can('client.clients.activate')
                                                <li>
                                                    <a href="#" data-toggle="modal"
                                                       data-target="#change_status_modal">
                                                        <em class="icon ni ni-check-circle"></em>
                                                        <span>{{trans_choice('client::general.change',1)}} {{trans_choice('core::general.status',1)}}</span>
                                                    </a>
                                                </li>
                                            @endcan
                                            @can('client.clients.edit')
                                                <li>
                                                    <a href="{{url('client/' . $client->id . '/edit')}}">
                                                        <em class="icon ni ni-edit"></em>
                                                        <span>{{trans_choice('core::general.edit',1)}}</span>
                                                    </a>
                                                </li>
                                                <li>
                                                    <a href="#" data-toggle="modal"
                                                       data-target="#transfer_client_modal"><em
                                                                class="icon ni ni-forward"></em>
                                                        <span>{{trans_choice('client::general.transfer',1)}}</span>
                                                    </a>
                                                </li>
                                            @endcan
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div><!-- .user-card -->
                    </div>
                    <div class="card-inner">

                        <ul class="data-list is-compact">
                            <li class="data-item">
                                <div class="data-col">
                                    <div class="data-label">
                                        {{trans_choice('core::general.branch',1)}}
                                    </div>
                                    <div class="data-value">
                                        @if(!empty($client->branch))
                                            {{$client->branch->name}}
                                        @endif
                                    </div>
                                </div>
                            </li>
                            <li class="data-item">
                                <div class="data-col">
                                    <div class="data-label">
                                        {{trans_choice('core::general.status',1)}}
                                    </div>
                                    <div class="data-value">
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
                                    </div>
                                </div>
                            </li>
                            <li class="data-item">
                                <div class="data-col">
                                    <div class="data-label">
                                        {{trans_choice('core::general.external_id',1)}}
                                    </div>
                                    <div class="data-value">
                                        {{$client->external_id}}
                                    </div>
                                </div>
                            </li>
                            <li class="data-item">
                                <div class="data-col">
                                    <div class="data-label">
                                        {{trans_choice('core::general.type',1)}}
                                    </div>
                                    <div class="data-value">
                                        @if(!empty($client->client_type))
                                            {{$client->client_type->name}}
                                        @endif
                                    </div>
                                </div>
                            </li>
                            <li class="data-item">
                                <div class="data-col">
                                    <div class="data-label">
                                        {{trans_choice('core::general.staff',1)}}
                                    </div>
                                    <div class="data-value">
                                        @if(!empty($client->loan_officer))
                                            {{$client->loan_officer->first_name}} {{$client->loan_officer->last_name}}
                                        @endif
                                    </div>
                                </div>
                            </li>
                            <li class="data-item">
                                <div class="data-col">
                                    <div class="data-label">
                                        {{trans_choice('core::general.mobile',1)}}
                                    </div>
                                    <div class="data-value">
                                        {{$client->mobile}}
                                    </div>
                                </div>
                            </li>
                            <li class="data-item">
                                <div class="data-col">
                                    <div class="data-label">
                                        {{trans_choice('core::general.email',1)}}
                                    </div>
                                    <div class="data-value">
                                        {{$client->email}}
                                    </div>
                                </div>
                            </li>
                            <li class="data-item">
                                <div class="data-col">
                                    <div class="data-label">
                                        {{trans_choice('core::general.dob',1)}}
                                    </div>
                                    <div class="data-value">
                                        {{$client->dob}}
                                    </div>
                                </div>
                            </li>
                            <li class="data-item">
                                <div class="data-col">
                                    <div class="data-label">
                                        {{trans_choice('core::general.gender',1)}}
                                    </div>
                                    <div class="data-value">
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
                                    </div>
                                </div>
                            </li>
                            <li class="data-item">
                                <div class="data-col">
                                    <div class="data-label">
                                        {{trans_choice('client::general.marital_status',1)}}
                                    </div>
                                    <div class="data-value">
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
                                    </div>
                                </div>
                            </li>
                            <li class="data-item">
                                <div class="data-col">
                                    <div class="data-label">
                                        {{trans_choice('core::general.zip',1)}}
                                    </div>
                                    <div class="data-value">
                                        {{$client->zip}}
                                    </div>
                                </div>
                            </li>
                            <li class="data-item">
                                <div class="data-col">
                                    <div class="data-label">
                                        {{trans_choice('core::general.joined_date',1)}}
                                    </div>
                                    <div class="data-value">
                                        {{$client->created_date}}
                                    </div>
                                </div>
                            </li>
                            @foreach($custom_fields as $custom_field)
                                <?php
                                $field = custom_field_build_form_field($custom_field, $client->id);
                                ?>
                                <li class="data-item">
                                    <div class="data-col">
                                        <div class="data-label">
                                            {{$field['label']}}
                                        </div>
                                        <div class="data-value">
                                            @if($custom_field->type=='checkbox')
                                                @foreach(explode(',',$field['current'] ) as $key)
                                                    {{$key}}<br>
                                                @endforeach
                                            @else
                                                {{$field['current'] }}
                                            @endif
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                            <li class="data-item">
                                <div class="data-col">
                                    <div class="data-label">
                                        {{trans_choice('core::general.activation_date',1)}}
                                    </div>
                                    <div class="data-value">
                                        {{$client->activation_date}}
                                    </div>
                                </div>
                            </li>
                        </ul>
                    </div>
                    <div class="card-inner">
                        <div class="user-account-info py-0">
                            <h6 class="overline-title-alt">
                                <i class="fa fa-map-marker margin-r-5"></i> {{trans_choice('core::general.address',1)}}
                            </h6>
                            <div class="">
                                {{$client->address}}<br>
                                @if(!empty($client->country))
                                    {{$client->country->name}}
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="card-inner">
                        <div class="user-account-info py-0">
                            <h6 class="overline-title-alt">
                                <i class="fa fa-file-text-o margin-r-5"></i> {{trans_choice('core::general.note',2)}}
                            </h6>
                            <div class="">
                                {{$client->notes}}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /.box -->
        </div>
        <!-- /.col -->
        <div class="col-md-9">
            <div class="nk-block nk-block-lg">
                <div class="card card-bordered card-preview">
                    <div class="card-inner">
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
                                       aria-expanded="true">{{trans_choice('client::general.next_of_kin',1)}}</a></li>
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
                                        <em class="icon ni ni-call d-none d-md-inline"></em>
                                        USSD Access
                                    </a>
                                </li>
                            @endcan
                        </ul>
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
                                        </tr>
                                        </thead>
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
                                                                        class="fa fa-edit"></i> </a>
                                                        @endcan
                                                        @can('client.clients.identification.destroy')
                                                            <a href="{{url('client/client_identification/'.$key->id.'/destroy')}}"
                                                               class="confirm"><i class="fa fa-trash"></i> </a>
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
                                                                    class="fa fa-edit"></i> </a>
                                                    @endcan
                                                    @can('client.clients.next_of_kin.destroy')
                                                        <a href="{{url('client/client_next_of_kin/'.$key->id.'/destroy')}}"
                                                           class="confirm"><i class="fa fa-trash"></i> </a>
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
                                                           class=""><i class="fa fa-edit"></i> </a>
                                                    @endcan
                                                    @can('client.clients.user.destroy')
                                                        <a href="{{url('client/user/'.$key->id.'/destroy')}}"
                                                           class="confirm"><i class="fa fa-trash"></i> </a>
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
                                                                        class="fa fa-edit"></i> </a>
                                                        @endcan
                                                        @can('client.clients.files.destroy')
                                                            <a href="{{url('client/file/'.$key->id.'/destroy')}}"
                                                               class="confirm"><i class="fa fa-trash"></i> </a>
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
                        <!-- /.tab-content -->
                    </div>
                </div>
            </div>
        </div>
        <!-- /.col -->
    </div>
    <div class="modal fade in" id="change_status_modal">
        <div class="modal-dialog">
            <div class="modal-content">
                <button type="button" class="close" data-dismiss="modal">
                    <span>×</span></button>
                <div class="modal-header">
                    <h4 class="modal-title">{{ trans_choice('client::general.change',1) }} {{ trans_choice('core::general.status',1) }}</h4>
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

    {{-- ── USSD Access Panel ───────────────────────────────────────────── --}}
    @can('sacco.ussd.members')
    <div class="nk-block mt-4" id="ussd">
        @php $allServices = \Modules\Sacco\Entities\UssdMemberConfig::ALL_SERVICES ?? []; @endphp

        {{-- Status card --}}
        <div class="card card-bordered mb-3">
            <div class="card-inner">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="card-title mb-1">
                            <em class="icon ni ni-call mr-1"></em> USSD Mobile Banking
                        </h6>
                        <p class="text-soft small mb-0">
                            Shortcode: <strong>{{ \Modules\Setting\Entities\Setting::where('setting_key','ussd.shortcode')->value('setting_value') ?? config('sacco.ussd_shortcode','*123#') }}</strong>
                        </p>
                    </div>
                    <div class="text-right">
                        @if($ussdConfig && $ussdConfig->isLocked())
                            <span class="badge badge-danger badge-lg">Locked</span>
                        @elseif($ussdConfig && $ussdConfig->ussd_enabled)
                            <span class="badge badge-success badge-lg">Enabled</span>
                        @else
                            <span class="badge badge-light badge-lg">Disabled</span>
                        @endif
                    </div>
                </div>

                @if($ussdConfig && $ussdConfig->ussd_enabled)
                <div class="row g-3 mt-2">
                    <div class="col-sm-3">
                        <span class="text-soft small d-block">PIN Set</span>
                        <span class="fw-medium">
                            @if($ussdConfig->pin_set)
                                <em class="icon ni ni-check-circle text-success"></em> Yes
                            @else
                                <em class="icon ni ni-cross-circle text-warning"></em> Not Set
                            @endif
                        </span>
                    </div>
                    <div class="col-sm-3">
                        <span class="text-soft small d-block">Failed Attempts</span>
                        <span class="fw-medium {{ $ussdConfig->failed_pin_attempts >= 2 ? 'text-warning' : '' }}">
                            {{ $ussdConfig->failed_pin_attempts }}
                        </span>
                    </div>
                    <div class="col-sm-3">
                        <span class="text-soft small d-block">Last Access</span>
                        <span class="fw-medium small">
                            {{ $ussdConfig->last_access_at ? $ussdConfig->last_access_at->diffForHumans() : 'Never' }}
                        </span>
                    </div>
                    <div class="col-sm-3">
                        <span class="text-soft small d-block">Daily Limit</span>
                        <span class="fw-medium">
                            {{ $ussdConfig->daily_transaction_limit
                                ? number_format($ussdConfig->daily_transaction_limit, 2)
                                : 'Global default' }}
                        </span>
                    </div>
                </div>

                @if(!empty($ussdConfig->allowed_services))
                <div class="mt-3">
                    <span class="text-soft small d-block mb-1">Enabled Services</span>
                    <div>
                        @foreach($ussdConfig->getAllowedServicesLabels() as $key => $label)
                            <span class="badge badge-dim badge-info mr-1 mb-1">{{ $label }}</span>
                        @endforeach
                    </div>
                </div>
                @endif
                @endif
            </div>
        </div>

        <div class="row g-3">
            {{-- Enable / Disable --}}
            <div class="col-md-4">
                <div class="card card-bordered h-100">
                    <div class="card-inner">
                        <h6 class="card-title mb-3">Access Control</h6>
                        @if($ussdConfig && $ussdConfig->ussd_enabled)
                            <form method="post" action="{{ url('sacco/ussd/member/'.$client->id.'/disable') }}">
                                @csrf
                                <p class="text-soft small">USSD is currently <strong class="text-success">active</strong> for this member.</p>
                                <button class="btn btn-sm btn-warning confirm"
                                        data-confirm="Disable USSD access for {{ $client->first_name }}?">
                                    <em class="icon ni ni-na mr-1"></em> Disable USSD
                                </button>
                            </form>
                        @else
                            <form method="post" action="{{ url('sacco/ussd/member/'.$client->id.'/enable') }}">
                                @csrf
                                <p class="text-soft small">USSD is currently <strong class="text-muted">not active</strong> for this member.</p>
                                <div class="form-group mb-2">
                                    <label class="form-label form-label-sm">Notes (optional)</label>
                                    <input type="text" name="notes" class="form-control form-control-sm"
                                           placeholder="e.g. Activated at branch">
                                </div>
                                <button class="btn btn-sm btn-success">
                                    <em class="icon ni ni-check-circle mr-1"></em> Enable USSD
                                </button>
                            </form>
                        @endif

                        @if($ussdConfig && $ussdConfig->isLocked())
                            <hr>
                            <form method="post" action="{{ url('sacco/ussd/member/'.$client->id.'/unlock') }}">
                                @csrf
                                <p class="text-soft small text-danger">Account is <strong>locked</strong> due to failed PIN attempts.</p>
                                <button class="btn btn-sm btn-info">
                                    <em class="icon ni ni-unlock mr-1"></em> Unlock Account
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>

            {{-- PIN Management --}}
            <div class="col-md-4">
                <div class="card card-bordered h-100">
                    <div class="card-inner">
                        <h6 class="card-title mb-3">PIN Management</h6>

                        <form method="post" action="{{ url('sacco/ussd/member/'.$client->id.'/reset-pin') }}"
                              class="confirm mb-3" data-confirm="Reset this member's USSD PIN? A temporary PIN will be generated.">
                            @csrf
                            <p class="text-soft small">Generate a temporary PIN and SMS it to the member.</p>
                            <button class="btn btn-sm btn-outline-secondary">
                                <em class="icon ni ni-reload mr-1"></em> Reset PIN
                            </button>
                        </form>

                        <hr>
                        <form method="post" action="{{ url('sacco/ussd/member/'.$client->id.'/set-pin') }}">
                            @csrf
                            <p class="text-soft small">Set a specific PIN for this member.</p>
                            <div class="form-group mb-2">
                                <input type="password" name="pin" class="form-control form-control-sm"
                                       placeholder="4-digit PIN" maxlength="4" pattern="\d{4}" required>
                            </div>
                            <div class="form-group mb-2">
                                <input type="password" name="pin_confirmation" class="form-control form-control-sm"
                                       placeholder="Confirm PIN" maxlength="4" pattern="\d{4}" required>
                            </div>
                            <button class="btn btn-sm btn-primary">
                                <em class="icon ni ni-lock-alt mr-1"></em> Set PIN
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            {{-- Services & Limits --}}
            <div class="col-md-4">
                <div class="card card-bordered h-100">
                    <div class="card-inner">
                        <h6 class="card-title mb-3">Services & Limits</h6>
                        <form method="post" action="{{ url('sacco/ussd/member/'.$client->id.'/set-services') }}">
                            @csrf
                            @php
                                $enabledServices = $ussdConfig->allowed_services ?? array_keys($allServices);
                            @endphp
                            <div class="mb-3">
                                @foreach($allServices as $key => $label)
                                    <div class="custom-control custom-checkbox mb-1">
                                        <input type="checkbox" class="custom-control-input"
                                               id="svc_{{ $key }}_{{ $client->id }}"
                                               name="allowed_services[]" value="{{ $key }}"
                                               {{ in_array($key, $enabledServices) ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="svc_{{ $key }}_{{ $client->id }}">
                                            {{ $label }}
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                            <div class="form-group mb-2">
                                <label class="form-label form-label-sm">Daily Transaction Limit</label>
                                <div class="input-group input-group-sm">
                                    <input type="number" name="daily_transaction_limit" class="form-control form-control-sm"
                                           value="{{ $ussdConfig->daily_transaction_limit ?? '' }}"
                                           placeholder="Leave blank for global default" min="0" step="100">
                                </div>
                            </div>
                            <button class="btn btn-sm btn-primary">
                                <em class="icon ni ni-save mr-1"></em> Save
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        {{-- Recent sessions --}}
        @if($ussdSessions->isNotEmpty())
        <div class="card card-bordered mt-3">
            <div class="card-inner">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="card-title mb-0">Recent USSD Sessions</h6>
                    <a href="{{ url('sacco/ussd/sessions?s='.$client->mobile) }}" class="btn btn-sm btn-outline-secondary">
                        View All
                    </a>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-borderless">
                        <thead class="thead-light">
                            <tr>
                                <th>Date</th>
                                <th>Session ID</th>
                                <th>Last Menu</th>
                                <th>Status</th>
                                <th>Duration</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($ussdSessions as $s)
                            <tr>
                                <td class="text-soft small">{{ $s->created_at->format('d M y H:i') }}</td>
                                <td><code class="small">{{ Str::limit($s->session_id, 16) }}</code></td>
                                <td>{{ $s->last_menu ?? '—' }}</td>
                                <td>
                                    <span class="badge badge-sm badge-{{
                                        $s->status === 'completed' ? 'success' :
                                        ($s->status === 'error' ? 'danger' :
                                        ($s->status === 'timeout' ? 'warning' : 'info'))
                                    }}">{{ $s->status }}</span>
                                </td>
                                <td class="text-soft small">
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
        </div>
        @endif
    </div>
    @endcan

@endsection
@section('scripts')

    <script>
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
    </script>

@endsection
