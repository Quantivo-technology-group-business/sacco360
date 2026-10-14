@extends('core::layouts.master')
@section('title') Agent KYC Requirements @endsection

@section('content')
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1><i class="fas fa-cog mr-2"></i>KYC Requirements</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{url('sacco/agent-kyc')}}">Agent KYC</a></li>
                    <li class="breadcrumb-item active">Requirements</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<section class="content">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <i class="fas fa-check-circle mr-2"></i>{{session('success')}}
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <i class="fas fa-exclamation-circle mr-2"></i>{{session('error')}}
        </div>
    @endif

    <div class="row">
        <div class="col-md-8">
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title">Document Requirements</h3>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Flags</th>
                                <th>Status</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($requirements as $req)
                            <tr class="{{!$req->active ? 'table-secondary' : ''}}">
                                <td><code>{{$req->code}}</code></td>
                                <td><strong>{{$req->name}}</strong></td>
                                <td>{{$req->category_label}}</td>
                                <td>
                                    @if($req->is_mandatory)
                                    <span class="badge badge-danger">Required</span>
                                    @endif
                                    @if($req->requires_verification)
                                    <span class="badge badge-info">Verification</span>
                                    @endif
                                </td>
                                <td>
                                    @if($req->active)
                                    <span class="badge badge-success">Active</span>
                                    @else
                                    <span class="badge badge-secondary">Inactive</span>
                                    @endif
                                </td>
                                <td class="text-right">
                                    <button type="button" class="btn btn-sm btn-outline-primary" 
                                            data-toggle="modal" data-target="#editModal{{$req->id}}">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <a href="{{url('sacco/agent-kyc/requirements/'.$req->id.'/delete')}}" 
                                       class="btn btn-sm btn-outline-danger confirm" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card card-success card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-plus mr-2"></i>Add Requirement</h3>
                </div>
                <div class="card-body">
                    <form action="{{url('sacco/agent-kyc/requirements/store')}}" method="post">
                        @csrf
                        <div class="form-group">
                            <label>Code <span class="text-danger">*</span></label>
                            <input type="text" name="code" class="form-control" required
                                   placeholder="e.g., tax_clearance" pattern="[a-z_]+"
                                   title="Lowercase letters and underscores only">
                            <small class="form-text text-muted">Unique identifier (lowercase, underscores)</small>
                        </div>
                        <div class="form-group">
                            <label>Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" required
                                   placeholder="e.g., Tax Clearance Certificate">
                        </div>
                        <div class="form-group">
                            <label>Category <span class="text-danger">*</span></label>
                            <select name="category" class="form-control" required>
                                @foreach($categories as $code => $label)
                                <option value="{{$code}}">{{$label}}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="description" class="form-control" rows="2"
                                      placeholder="Brief description..."></textarea>
                        </div>
                        <div class="form-group">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="is_mandatory" name="is_mandatory" value="1">
                                <label class="custom-control-label" for="is_mandatory">Mandatory for KYC approval</label>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="requires_verification" name="requires_verification" value="1">
                                <label class="custom-control-label" for="requires_verification">Requires external verification</label>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-success btn-block">
                            <i class="fas fa-plus mr-2"></i>Add Requirement
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- Edit Modals --}}
@foreach($requirements as $req)
<div class="modal fade" id="editModal{{$req->id}}" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{url('sacco/agent-kyc/requirements/'.$req->id.'/update')}}" method="post">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Edit: {{$req->name}}</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Code</label>
                        <input type="text" class="form-control" value="{{$req->code}}" disabled>
                    </div>
                    <div class="form-group">
                        <label>Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="{{$req->name}}" required>
                    </div>
                    <div class="form-group">
                        <label>Category <span class="text-danger">*</span></label>
                        <select name="category" class="form-control" required>
                            @foreach($categories as $code => $label)
                            <option value="{{$code}}" {{$req->category == $code ? 'selected' : ''}}>{{$label}}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="2">{{$req->description}}</textarea>
                    </div>
                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="is_mandatory{{$req->id}}" 
                                   name="is_mandatory" value="1" {{$req->is_mandatory ? 'checked' : ''}}>
                            <label class="custom-control-label" for="is_mandatory{{$req->id}}">Mandatory</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="requires_verification{{$req->id}}" 
                                   name="requires_verification" value="1" {{$req->requires_verification ? 'checked' : ''}}>
                            <label class="custom-control-label" for="requires_verification{{$req->id}}">Requires Verification</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="active{{$req->id}}" 
                                   name="active" value="1" {{$req->active ? 'checked' : ''}}>
                            <label class="custom-control-label" for="active{{$req->id}}">Active</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach
@endsection
