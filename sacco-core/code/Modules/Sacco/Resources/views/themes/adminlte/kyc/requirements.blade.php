@extends('core::layouts.master')
@section('title') KYC Requirements @endsection
@section('content')
<section class="content-header"><div class="container-fluid"><h1>KYC Document Requirements</h1><a href="{{url('sacco/kyc')}}" class="btn btn-default btn-sm">Back</a></div></section>
<section class="content">
    <div class="row">
        <div class="col-md-8">
            <div class="card"><div class="card-body table-responsive">
                <table class="table table-striped">
                    <thead><tr><th>Code</th><th>Name</th><th>Category</th><th>Mandatory</th><th>IPRS</th><th>Active</th><th>Applies To</th><th></th></tr></thead>
                    <tbody>
                    @foreach($requirements as $r)
                    <tr>
                        <td>{{$r->code}}</td>
                        <td>{{$r->name}}</td>
                        <td>{{ucfirst($r->category)}}</td>
                        <td>{{$r->mandatory ? 'Yes' : 'No'}}</td>
                        <td>{{$r->requires_iprs ? 'Yes' : 'No'}}</td>
                        <td>{{$r->active ? 'Yes' : 'No'}}</td>
                        <td>{{ucfirst($r->applies_to)}}</td>
                        <td><a href="{{url('sacco/kyc/requirements/'.$r->id.'/toggle')}}" class="btn btn-xs btn-default">{{$r->active ? 'Disable' : 'Enable'}}</a></td>
                    </tr>
                    @endforeach
                    </tbody>
                </table>
            </div></div>
        </div>
        <div class="col-md-4">
            <div class="card"><div class="card-header"><h3 class="card-title">Add Requirement</h3></div>
            <div class="card-body">
                <form method="post" action="{{url('sacco/kyc/requirements')}}">@csrf
                    <div class="form-group"><label>Code</label><input type="text" name="code" class="form-control" required></div>
                    <div class="form-group"><label>Name</label><input type="text" name="name" class="form-control" required></div>
                    <div class="form-group"><label>Category</label>
                        <select name="category" class="form-control">
                            @foreach(['identity','tax','address','employment','biometric','other'] as $cat)
                            <option value="{{$cat}}">{{ucfirst($cat)}}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group"><label>Applies To</label>
                        <select name="applies_to" class="form-control">
                            <option value="both">Both</option>
                            <option value="individual">Individual</option>
                            <option value="organisation">Organisation</option>
                        </select>
                    </div>
                    <div class="form-group"><label><input type="checkbox" name="mandatory" value="1" checked> Mandatory</label></div>
                    <div class="form-group"><label><input type="checkbox" name="requires_iprs" value="1"> Requires IPRS</label></div>
                    <button type="submit" class="btn btn-primary">Add</button>
                </form>
            </div></div>
        </div>
    </div>
</section>
@endsection
