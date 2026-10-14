@extends('core::layouts.master')
@section('title')
    {{ trans_choice('core::general.add',1) }} {{ trans_choice('user::general.role',1) }}
@endsection
@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>
                        {{ trans_choice('core::general.add',1) }} {{ trans_choice('user::general.role',1) }}
                        <a href="#" onclick="window.history.back()"
                           class="btn btn-outline-light bg-white d-none d-sm-inline-flex">
                            <em class="icon ni ni-arrow-left"></em><span>{{ trans_choice('core::general.back',1) }}</span>
                        </a>
                    </h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{url('dashboard')}}">{{ trans_choice('dashboard::general.dashboard',1) }}</a></li>
                        <li class="breadcrumb-item"><a href="{{url('user/role')}}">{{ trans_choice('user::general.role',2) }}</a></li>
                        <li class="breadcrumb-item active">{{ trans_choice('core::general.add',1) }} {{ trans_choice('user::general.role',1) }}</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>
    <section class="content" id="app">
        <form method="post" action="{{ url('user/role/store') }}">
            {{csrf_field()}}
            <div class="card card-bordered card-preview">
                <div class="card-body">
                    <div class="row gy-4">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="name" class="control-label">{{trans_choice('core::general.name',1)}}</label>
                                <input type="text" name="name" v-model="name" id="name"
                                       class="form-control @error('name') is-invalid @enderror" required>
                                @error('name')<span class="invalid-feedback"><strong>{{ $message }}</strong></span>@enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="parent_role_id" class="control-label">{{ trans_choice('user::general.inherits_from', 1) }}</label>
                                <select name="parent_role_id" id="parent_role_id" class="form-control">
                                    <option value="">{{ trans_choice('core::general.none', 1) }}</option>
                                    @foreach($roles as $parentRole)
                                        <option value="{{ $parentRole->id }}">{{ $parentRole->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="description" class="control-label">{{ trans_choice('core::general.description', 1) }}</label>
                                <textarea name="description" id="description" class="form-control" rows="2"></textarea>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <h5>{{ trans_choice('user::general.permission', 2) }}</h5>
                    @include('user::partials.permission_tree', ['permissionTree' => $permissionTree, 'selectedPermissions' => $selected_permissions])
                </div>
                <div class="card-footer border-top">
                    <button type="submit" class="btn btn-primary float-right">{{trans_choice('core::general.save',1)}}</button>
                </div>
            </div>
        </form>
    </section>
@endsection
@section('scripts')
    <script>
        var app = new Vue({
            el: "#app",
            data: {
                name: "",
                selectedPermissions: {!! json_encode($selected_permissions) !!},
            },
        });
        $('.module-toggle').on('change', function () {
            var module = $(this).data('module');
            $('.perm-checkbox[data-module="' + module + '"]').prop('checked', this.checked).trigger('change');
        });
    </script>
@endsection
