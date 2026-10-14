@extends('core::layouts.master')
@section('title') Create Group / Chama @endsection
@section('content')
<section class="content-header">
    <div class="container-fluid"><h1>Create Group / Chama</h1></div>
</section>
<section class="content">
    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ url('sacco/group/store') }}">
                @csrf
                <div class="row">
                    <div class="col-md-5">
                        <div class="form-group">
                            <label>Group Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Code</label>
                            <input type="text" name="code" class="form-control" value="{{ old('code') }}" placeholder="Auto-generated if blank">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Type <span class="text-danger">*</span></label>
                            <select name="type" class="form-control" required>
                                <option value="chama">Chama</option>
                                <option value="merry_go_round">Merry-Go-Round</option>
                                <option value="table_banking">Table Banking</option>
                                <option value="investment_club">Investment Club</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Branch</label>
                            <select name="branch_id" class="form-control">
                                <option value="">— All —</option>
                                @foreach($branches as $b)
                                    <option value="{{ $b->id }}" {{ old('branch_id')==$b->id?'selected':'' }}>{{ $b->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Monthly Contribution</label>
                            <input type="number" name="monthly_contribution" class="form-control" step="0.01" min="0" value="{{ old('monthly_contribution',0) }}">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Share Value</label>
                            <input type="number" name="share_value" class="form-control" step="0.01" min="0" value="{{ old('share_value',0) }}">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Max Members</label>
                            <input type="number" name="max_members" class="form-control" min="1" value="{{ old('max_members') }}">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Rotation Model</label>
                            <select name="rotation_model" class="form-control">
                                <option value="fixed" {{ old('rotation_model','fixed')==='fixed'?'selected':'' }}>Fixed</option>
                                <option value="random" {{ old('rotation_model')==='random'?'selected':'' }}>Random</option>
                                <option value="bidding" {{ old('rotation_model')==='bidding'?'selected':'' }}>Bidding</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Late Penalty</label>
                            <input type="number" name="late_payment_penalty" class="form-control" step="0.01" min="0" value="{{ old('late_payment_penalty',0) }}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Registration Number</label>
                            <input type="text" name="registration_number" class="form-control" value="{{ old('registration_number') }}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Registration Date</label>
                            <input type="date" name="registration_date" class="form-control" value="{{ old('registration_date') }}">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Chairperson</label>
                            <select name="chairperson_client_id" class="form-control select2">
                                <option value="">— None —</option>
                                @foreach($clients as $c)
                                    <option value="{{ $c->id }}" {{ old('chairperson_client_id')==$c->id?'selected':'' }}>{{ $c->full_name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Secretary</label>
                            <select name="secretary_client_id" class="form-control select2">
                                <option value="">— None —</option>
                                @foreach($clients as $c)
                                    <option value="{{ $c->id }}" {{ old('secretary_client_id')==$c->id?'selected':'' }}>{{ $c->full_name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Treasurer</label>
                            <select name="treasurer_client_id" class="form-control select2">
                                <option value="">— None —</option>
                                @foreach($clients as $c)
                                    <option value="{{ $c->id }}" {{ old('treasurer_client_id')==$c->id?'selected':'' }}>{{ $c->full_name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label>Purpose / Description</label>
                    <textarea name="purpose" class="form-control" rows="2">{{ old('purpose') }}</textarea>
                </div>
                <button type="submit" class="btn btn-primary">Save Group</button>
                <a href="{{ url('sacco/group') }}" class="btn btn-default">Cancel</a>
            </form>
        </div>
    </div>
</section>
@endsection
