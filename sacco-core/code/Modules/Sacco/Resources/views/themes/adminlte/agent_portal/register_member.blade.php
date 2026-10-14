@extends('core::layouts.master')
@section('title') Agent Portal - Register New Member @endsection
@section('content')

<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1><i class="fas fa-user-plus mr-2 text-success"></i>Register New Member</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{route('agent.dashboard')}}">Agent Portal</a></li>
                    <li class="breadcrumb-item active">Register Member</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<section class="content">
    <div class="row">
        <div class="col-md-8">
            <div class="card card-success">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="fas fa-user-plus mr-2"></i>New Member Registration</h5>
                </div>
                <div class="card-body">
                    <form id="registrationForm" method="POST" action="{{route('agent.register_member.process')}}">
                        @csrf
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>First Name <span class="text-danger">*</span></label>
                                    <input type="text" name="first_name" class="form-control" required value="{{old('first_name')}}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Middle Name</label>
                                    <input type="text" name="middle_name" class="form-control" value="{{old('middle_name')}}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Last Name <span class="text-danger">*</span></label>
                                    <input type="text" name="last_name" class="form-control" required value="{{old('last_name')}}">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Mobile Phone <span class="text-danger">*</span></label>
                                    <input type="tel" name="mobile" class="form-control" required placeholder="+1234567890" value="{{old('mobile')}}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Email Address</label>
                                    <input type="email" name="email" class="form-control" placeholder="email@example.com" value="{{old('email')}}">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Date of Birth</label>
                                    <input type="date" name="dob" class="form-control" value="{{old('dob')}}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Gender</label>
                                    <select name="gender" class="form-control">
                                        <option value="">-- Select --</option>
                                        <option value="male" {{old('gender') == 'male' ? 'selected' : ''}}>Male</option>
                                        <option value="female" {{old('gender') == 'female' ? 'selected' : ''}}>Female</option>
                                        <option value="other" {{old('gender') == 'other' ? 'selected' : ''}}>Other</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>ID Number</label>
                                    <input type="text" name="id_number" class="form-control" placeholder="National ID" value="{{old('id_number')}}">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Address</label>
                            <textarea name="address" class="form-control" rows="2" placeholder="Physical address...">{{old('address')}}</textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Registration Fee</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend"><span class="input-group-text">$</span></div>
                                        <input type="number" step="0.01" min="0" name="registration_fee" class="form-control" value="{{old('registration_fee', 0)}}" placeholder="0.00">
                                    </div>
                                    <small class="text-muted">Amount collected from member for registration</small>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Notes</label>
                            <textarea name="notes" class="form-control" rows="2" placeholder="Any additional notes...">{{old('notes')}}</textarea>
                        </div>

                        <div class="alert alert-info">
                            <i class="fas fa-info-circle mr-2"></i>
                            The registration will be submitted for approval. You will earn a commission once the member is approved.
                        </div>

                        <button type="submit" class="btn btn-success btn-lg btn-block">
                            <i class="fas fa-user-plus mr-2"></i> Submit Registration
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card card-info card-outline">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="fas fa-info-circle mr-2"></i>Registration Guidelines</h5>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2"><i class="fas fa-check text-success mr-2"></i>Verify member's identity before registration</li>
                        <li class="mb-2"><i class="fas fa-check text-success mr-2"></i>Ensure phone number is correct</li>
                        <li class="mb-2"><i class="fas fa-check text-success mr-2"></i>Collect registration fee if applicable</li>
                        <li class="mb-2"><i class="fas fa-clock text-warning mr-2"></i>Registration requires admin approval</li>
                        <li class="mb-2"><i class="fas fa-coins text-info mr-2"></i>Commission is credited after approval</li>
                    </ul>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="fas fa-history mr-2"></i>My Registrations</h5>
                </div>
                <div class="card-body">
                    <a href="{{route('agent.member_registrations')}}" class="btn btn-outline-primary btn-block">
                        <i class="fas fa-list mr-1"></i> View All Registrations
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection
