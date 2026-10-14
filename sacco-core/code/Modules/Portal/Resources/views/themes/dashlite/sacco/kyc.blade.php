@extends('core::layouts.master')
@section('title') My KYC @endsection
@section('content')
<section class="content-header"><div class="container-fluid"><h1>My KYC Documents</h1></div></section>
<section class="content">
    @if(session('success'))<div class="alert alert-success">{{session('success')}}</div>@endif
    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body text-center">
                    <h3>{{$completion_percent}}% Complete</h3>
                    <p>Status: <strong>{{ucfirst(str_replace('_',' ',$kyc_status))}}</strong></p>
                    <p>IPRS: @if($iprs_verified) Verified @else Pending @endif</p>
                </div>
            </div>
            <div class="card">
                <div class="card-header"><h3 class="card-title">Submit Document</h3></div>
                <div class="card-body">
                    <form method="post" action="{{url('portal/sacco/kyc/document')}}" enctype="multipart/form-data">@csrf
                        <div class="form-group">
                            <label>Document Type</label>
                            <select name="document_type" class="form-control" required>
                                @foreach($requirements as $r)
                                @if($r->code !== 'signature')
                                <option value="{{$r->code}}">{{$r->name}}</option>
                                @endif
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group"><label>Reference / Number</label><input type="text" name="document_number" class="form-control"></div>
                        <div class="form-group"><label>File (JPG, PNG, PDF)</label><input type="file" name="file" class="form-control-file" required accept=".jpg,.jpeg,.png,.pdf">
                        @include('core::partials.upload_template', ['templateKey' => 'kyc.document'])</div>
                        <button type="submit" class="btn btn-primary btn-block">Submit for Review</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card"><div class="card-header"><h3 class="card-title">Your KYC Checklist</h3></div>
            <div class="card-body">
                <table class="table">
                    <thead><tr><th>Document</th><th>Required</th><th>Status</th></tr></thead>
                    <tbody>
                    @foreach($checklist as $item)
                    <tr>
                        <td>{{$item['requirement']->name}}</td>
                        <td>{{$item['requirement']->mandatory ? 'Yes' : 'No'}}</td>
                        <td>{{ucfirst($item['status'])}}</td>
                    </tr>
                    @endforeach
                    </tbody>
                </table>
                <p class="text-muted small">Contact your SACCO branch to complete IPRS/NIDA verification and final approval.</p>
            </div></div>
        </div>
    </div>
</section>
@endsection
