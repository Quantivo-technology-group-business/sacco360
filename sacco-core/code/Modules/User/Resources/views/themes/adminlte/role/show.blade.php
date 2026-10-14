@extends('core::layouts.master')
@section('title')
    {{ $role->name }}
@endsection
@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>{{ $role->name }}</h1>
                </div>
            </div>
        </div>
    </section>
    <section class="content" id="app">
        <div class="card card-bordered card-preview">
            <div class="card-body">
                <div class="row gy-4">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="control-label">{{trans_choice('core::general.name',1)}}</label>
                            <input type="text" value="{{ $role->name }}" class="form-control" disabled>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="control-label">{{ trans_choice('user::general.inherits_from', 1) }}</label>
                            <input type="text" value="{{ optional($role->parentRole)->name ?? trans_choice('core::general.none', 1) }}" class="form-control" disabled>
                        </div>
                    </div>
                    @if($role->description)
                        <div class="col-md-12">
                            <div class="form-group">
                                <label class="control-label">{{ trans_choice('core::general.description', 1) }}</label>
                                <textarea class="form-control" disabled>{{ $role->description }}</textarea>
                            </div>
                        </div>
                    @endif
                </div>
                <hr>
                <h5>{{ trans_choice('user::general.permission', 2) }} ({{ trans_choice('user::general.direct', 1) }})</h5>
                @include('user::themes.adminlte.partials.permission_tree', ['permissionTree' => $permissionTree, 'selectedPermissions' => $selected_permissions, 'readonly' => true])
                @if(!empty($inherited_permissions))
                    <hr>
                    <h5>{{ trans_choice('user::general.inherited_permission', 2) }}</h5>
                    <ul>
                        @foreach(\Modules\User\Entities\Permission::whereIn('id', $inherited_permissions)->get() as $perm)
                            <li>{{ $perm->display_name }} <small class="text-muted">({{ $perm->name }})</small></li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </section>
@endsection
