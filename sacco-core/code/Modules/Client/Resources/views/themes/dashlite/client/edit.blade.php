@extends('core::layouts.master')
@section('title')
    {{ trans_choice('core::general.edit',1) }} {{ trans_choice('client::general.client',1) }}
@endsection
@section('content')
    <div class="nk-block-head-content mb-4">
        <div class="nk-block-head nk-block-head-sm">
            <div class="nk-block-between">
                <div class="nk-block-head-content">
                    <h3 class="nk-block-title page-title">{{ trans_choice('core::general.edit',1) }} {{ trans_choice('client::general.client',1) }}</h3>
                </div>
                <div class="nk-block-head-content">
                    <a href="#" onclick="window.history.back()"
                       class="btn btn-outline-light bg-white d-none d-sm-inline-flex">
                        <em class="icon ni ni-arrow-left"></em><span>{{ trans_choice('core::general.back',1) }}</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Step indicator --}}
    <div class="nk-block mb-4" id="onboard-steps">
        <div class="card card-bordered">
            <div class="card-inner py-3">
                <ul class="nk-stepper stepper-s1 nk-stepper-s1 row gy-3 is-vr" id="stepperReg">
                    <li class="col stepper-item" :class="{ current: currentStep === 1, done: currentStep > 1 }">
                        <div class="stepper-head">
                            <div class="stepper-head-item"><em class="icon ni ni-user-alt"></em></div>
                            <div class="stepper-head-text d-none d-md-block">
                                <span class="stepper-title">Profile Info</span>
                            </div>
                        </div>
                    </li>
                    <li class="col stepper-item" :class="{ current: currentStep === 2, done: currentStep > 2 }">
                        <div class="stepper-head">
                            <div class="stepper-head-item"><em class="icon ni ni-call"></em></div>
                            <div class="stepper-head-text d-none d-md-block">
                                <span class="stepper-title">Contact & BMU</span>
                            </div>
                        </div>
                    </li>
                    <li class="col stepper-item" :class="{ current: currentStep === 3, done: currentStep > 3 }">
                        <div class="stepper-head">
                            <div class="stepper-head-item"><em class="icon ni ni-briefcase"></em></div>
                            <div class="stepper-head-text d-none d-md-block">
                                <span class="stepper-title">Directors / Kin</span>
                            </div>
                        </div>
                    </li>
                    <li class="col stepper-item" :class="{ current: currentStep === 4 }">
                        <div class="stepper-head">
                            <div class="stepper-head-item"><em class="icon ni ni-check-thick"></em></div>
                            <div class="stepper-head-text d-none d-md-block">
                                <span class="stepper-title">Review</span>
                            </div>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <div class="nk-block nk-block-lg" id="app">
        <form method="post" action="{{ url('client/'.$client->id.'/update') }}" enctype="multipart/form-data">
            {{csrf_field()}}
            <input type="hidden" name="last_name" :value="last_name">

            {{-- Step 1: Profile Info --}}
            <div v-show="currentStep === 1">
                <div class="card card-bordered card-preview">
                    <div class="card-inner">
                        <h5 class="card-title mb-4"><em class="icon ni ni-user-alt mr-1"></em> Profile Information</h5>
                        <div class="row gy-4">
                            <div class="col-md-12 mb-2">
                                <div class="form-group">
                                    <label class="control-label">{{trans_choice('client::general.type',1)}} <span class="text-danger">*</span></label>
                                    <select class="form-control" name="client_type_id" v-model="client_type_id" required>
                                        @foreach($client_types as $key)
                                            <option value="{{$key->id}}">{{$key->name}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            {{-- Individual fields --}}
                            <template v-if="!isOrganisation">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="control-label">{{trans_choice('client::general.title',1)}}</label>
                                        <select class="form-control" name="title_id" v-model="title_id">
                                            <option value="">{{trans_choice('core::general.select',1)}}</option>
                                            @foreach($titles as $key)
                                                <option value="{{$key->id}}">{{$key->name}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="control-label">{{trans_choice('core::general.gender',1)}} <span class="text-danger">*</span></label>
                                        <select class="form-control" name="gender" v-model="gender" :required="!isOrganisation">
                                            <option value="">{{trans_choice('core::general.select',1)}}</option>
                                            <option value="male">{{trans_choice("core::general.male",1)}}</option>
                                            <option value="female">{{trans_choice("core::general.female",1)}}</option>
                                            <option value="other">{{trans_choice("core::general.other",1)}}</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="control-label">Full Name <span class="text-danger">*</span></label>
                                        <input type="text" name="first_name" v-model="first_name" class="form-control" :required="!isOrganisation">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="control-label">Date of Birth <span class="text-danger">*</span></label>
                                        <flat-pickr v-model="dob" class="form-control" name="dob"></flat-pickr>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="control-label">ID Number <span class="text-danger">*</span></label>
                                        <input type="text" name="id_number" v-model="id_number" class="form-control" :required="!isOrganisation">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="control-label">Passport Number</label>
                                        <input type="text" name="passport_number" v-model="passport_number" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="control-label">{{trans_choice('client::general.marital_status',1)}}</label>
                                        <select class="form-control" name="marital_status" v-model="marital_status">
                                            <option value=""></option>
                                            <option value="single">{{trans_choice("client::general.single",1)}}</option>
                                            <option value="married">{{trans_choice("client::general.married",1)}}</option>
                                            <option value="divorced">{{trans_choice("client::general.divorced",1)}}</option>
                                            <option value="widowed">{{trans_choice("client::general.widowed",1)}}</option>
                                        </select>
                                    </div>
                                </div>
                            </template>

                            {{-- Organisation fields --}}
                            <template v-else>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="control-label">Organisation Name <span class="text-danger">*</span></label>
                                        <input type="text" name="first_name" v-model="first_name" class="form-control" :required="isOrganisation">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="control-label">Registration Number <span class="text-danger">*</span></label>
                                        <input type="text" name="registration_number" v-model="registration_number" class="form-control" :required="isOrganisation">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="control-label">Registration Date <span class="text-danger">*</span></label>
                                        <flat-pickr v-model="registration_date" class="form-control" name="registration_date"></flat-pickr>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="control-label">KRA PIN</label>
                                        <input type="text" name="kra_pin" v-model="kra_pin" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="control-label">Nature of Business</label>
                                        <input type="text" name="nature_of_business" v-model="nature_of_business" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="control-label">Business Category</label>
                                        <input type="text" name="business_category" v-model="business_category" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="control-label">Years in Business</label>
                                        <input type="number" name="years_in_business" v-model="years_in_business" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="control-label">Membership Number</label>
                                        <input type="text" name="membership_number" v-model="membership_number" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="control-label">Date of Admission</label>
                                        <flat-pickr v-model="date_of_admission" class="form-control" name="date_of_admission"></flat-pickr>
                                    </div>
                                </div>
                            </template>

                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="control-label">{{trans_choice('core::general.photo',1)}}</label>
                                    @if($client->photo)
                                        <div class="mb-2">
                                            <img src="{{asset('storage/uploads/clients/'.$client->photo)}}" width="100" class="img-thumbnail">
                                        </div>
                                    @endif
                                    <input type="file" name="photo" class="form-control">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer border-top d-flex justify-content-end">
                        <button type="button" class="btn btn-primary" @click="goToStep(2)" :disabled="!canProceedStep1">
                            Next <em class="icon ni ni-arrow-right ml-1"></em>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Step 2: Contact & Group --}}
            <div v-show="currentStep === 2">
                <div class="card card-bordered card-preview">
                    <div class="card-inner">
                        <h5 class="card-title mb-4"><em class="icon ni ni-call mr-1"></em> Contact, Location & BMU Group</h5>
                        <div class="row gy-4">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="control-label">@{{ isOrganisation ? 'Telephone Number' : 'Mobile Number' }}</label>
                                    <input type="text" name="mobile" v-model="mobile" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="control-label">Alternative Mobile</label>
                                    <input type="text" name="alternative_mobile" v-model="alternative_mobile" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="control-label">Email Address</label>
                                    <input type="email" name="email" v-model="email" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="control-label">BMU / Sacco Group</label>
                                    <select class="form-control" name="sacco_group_id" v-model="sacco_group_id">
                                        <option value="">-- Select BMU / Group --</option>
                                        @foreach($sacco_groups as $group)
                                            <option value="{{$group->id}}">{{$group->name}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="control-label">Landing Site</label>
                                    <input type="text" name="landing_site" v-model="landing_site" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="control-label">{{trans_choice('core::general.country',1)}}</label>
                                    <select class="form-control" name="country_id" v-model="country_id">
                                        <option value=""></option>
                                        @foreach($countries as $key)
                                            <option value="{{$key->id}}">{{$key->name}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="control-label">Postal Address</label>
                                    <textarea name="address" v-model="address" rows="2" class="form-control"></textarea>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="control-label">Postal Code</label>
                                    <input type="text" name="postal_code" v-model="postal_code" class="form-control">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer border-top d-flex justify-content-between">
                        <button type="button" class="btn btn-outline-secondary" @click="currentStep = 1">
                            <em class="icon ni ni-arrow-left mr-1"></em> Back
                        </button>
                        <button type="button" class="btn btn-primary" @click="currentStep = 3">
                            Next <em class="icon ni ni-arrow-right ml-1"></em>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Step 3: Directors / Next of Kin --}}
            <div v-show="currentStep === 3">
                <div class="card card-bordered card-preview">
                    <div class="card-inner">
                        <h5 class="card-title mb-4">
                            <em class="icon ni ni-briefcase mr-1"></em> 
                            @{{ isOrganisation ? 'Directors & Witnesses' : 'Next of Kin & Employment' }}
                        </h5>
                        <div class="row gy-4">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="control-label">{{trans_choice('core::general.branch',1)}} <span class="text-danger">*</span></label>
                                    <select class="form-control" name="branch_id" v-model="branch_id" required>
                                        <option value="" disabled selected>{{trans_choice('core::general.select',1)}}</option>
                                        @foreach($branches as $key)
                                            <option value="{{$key->id}}">{{$key->name}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="control-label">{{trans_choice('core::general.staff',1)}}</label>
                                    <select class="form-control" name="loan_officer_id" v-model="loan_officer_id">
                                        <option value=""></option>
                                        @foreach($users as $key)
                                            <option value="{{$key->id}}">{{$key->first_name}} {{$key->last_name}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            {{-- Individual Next of Kin & Employment --}}
                            <template v-if="!isOrganisation">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="control-label">Employment Status</label>
                                        <input type="text" name="employment_status" v-model="employment_status" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="control-label">Employer Name</label>
                                        <input type="text" name="employer_name" v-model="employer_name" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="control-label">Workplace Address</label>
                                        <input type="text" name="workplace_address" v-model="workplace_address" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="control-label">Employer Phone</label>
                                        <input type="text" name="employer_phone" v-model="employer_phone" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="control-label">Occupation</label>
                                        <input type="text" name="occupation" v-model="occupation" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="control-label">Years in Business</label>
                                        <input type="number" name="years_in_business" v-model="years_in_business" class="form-control">
                                    </div>
                                </div>

                                <div class="col-md-12 mt-4">
                                    <h6 class="text-primary border-bottom pb-2 mb-3">Next of Kin Details</h6>
                                    <div v-for="(nok, index) in next_of_kin" :key="index" class="card card-bordered p-3 mb-3">
                                        <div class="d-flex justify-content-between mb-2">
                                            <span class="font-weight-bold">Next of Kin #@{{ index + 1 }}</span>
                                            <button type="button" class="btn btn-danger btn-xs" @click="removeNextOfKin(index)">
                                                Remove
                                            </button>
                                        </div>
                                        <div class="row gy-3">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="control-label">Full Name <span class="text-danger">*</span></label>
                                                    <input type="text" :name="'next_of_kin['+index+'][full_name]'" v-model="nok.full_name" class="form-control" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="control-label">ID Number</label>
                                                    <input type="text" :name="'next_of_kin['+index+'][id_number]'" v-model="nok.id_number" class="form-control">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="control-label">Phone Number</label>
                                                    <input type="text" :name="'next_of_kin['+index+'][phone_number]'" v-model="nok.phone_number" class="form-control">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="control-label">Relationship</label>
                                                    <input type="text" :name="'next_of_kin['+index+'][relationship]'" v-model="nok.relationship" class="form-control">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <button type="button" class="btn btn-outline-primary btn-sm" @click="addNextOfKin">
                                        <em class="icon ni ni-plus mr-1"></em> Add Next of Kin
                                    </button>
                                </div>
                            </template>

                            {{-- Organisation directors & witnesses --}}
                            <template v-else>
                                <div class="col-md-12 mt-3">
                                    <h6 class="text-primary border-bottom pb-2 mb-3">Directors</h6>
                                    <div v-for="(dir, index) in directors" :key="index" class="card card-bordered p-3 mb-3">
                                        <div class="d-flex justify-content-between mb-2">
                                            <span class="font-weight-bold">Director #@{{ index + 1 }}</span>
                                            <button type="button" class="btn btn-danger btn-xs" @click="removeDirector(index)">
                                                Remove
                                            </button>
                                        </div>
                                        <input type="hidden" :name="'directors['+index+'][id]'" v-model="dir.id">
                                        <div class="row gy-3">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="control-label">Full Name <span class="text-danger">*</span></label>
                                                    <input type="text" :name="'directors['+index+'][full_name]'" v-model="dir.full_name" class="form-control" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="control-label">ID Number</label>
                                                    <input type="text" :name="'directors['+index+'][id_number]'" v-model="dir.id_number" class="form-control">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="control-label">Passport Number</label>
                                                    <input type="text" :name="'directors['+index+'][passport_number]'" v-model="dir.passport_number" class="form-control">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="control-label">KRA PIN</label>
                                                    <input type="text" :name="'directors['+index+'][kra_pin]'" v-model="dir.kra_pin" class="form-control">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="control-label">Phone Number</label>
                                                    <input type="text" :name="'directors['+index+'][phone_number]'" v-model="dir.phone_number" class="form-control">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="control-label">Position</label>
                                                    <input type="text" :name="'directors['+index+'][position]'" v-model="dir.position" class="form-control">
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label class="control-label">Photo</label>
                                                    <input type="file" :name="'directors['+index+'][photo]'" class="form-control-file">
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label class="control-label">ID Copy</label>
                                                    <input type="file" :name="'directors['+index+'][id_copy]'" class="form-control-file">
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label class="control-label">PIN Certificate</label>
                                                    <input type="file" :name="'directors['+index+'][pin_certificate]'" class="form-control-file">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <button type="button" class="btn btn-outline-primary btn-sm" @click="addDirector">
                                        <em class="icon ni ni-plus mr-1"></em> Add Director
                                    </button>
                                </div>

                                <div class="col-md-12 mt-4">
                                    <h6 class="text-primary border-bottom pb-2 mb-3">Witnesses (Authorization)</h6>
                                    <div class="row gy-3">
                                        <div v-for="(wit, index) in witnesses" :key="index" class="col-md-6">
                                            <div class="card card-bordered p-3">
                                                <span class="font-weight-bold mb-2">Witness @{{ index + 1 }}</span>
                                                <div class="form-group">
                                                    <label class="control-label">Name</label>
                                                    <input type="text" :name="'witnesses['+index+'][name]'" v-model="wit.name" class="form-control">
                                                </div>
                                                <div class="form-group">
                                                    <label class="control-label">Position</label>
                                                    <input type="text" :name="'witnesses['+index+'][position]'" v-model="wit.position" class="form-control">
                                                </div>
                                                <div class="form-group">
                                                    <label class="control-label">Signature (Text representation)</label>
                                                    <input type="text" :name="'witnesses['+index+'][signature]'" v-model="wit.signature" class="form-control">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                    <div class="card-footer border-top d-flex justify-content-between">
                        <button type="button" class="btn btn-outline-secondary" @click="currentStep = 2">
                            <em class="icon ni ni-arrow-left mr-1"></em> Back
                        </button>
                        <button type="button" class="btn btn-primary" @click="goToStep(4)" :disabled="!canProceedStep3">
                            Review <em class="icon ni ni-arrow-right mr-1"></em>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Step 4: Review & Submit --}}
            <div v-show="currentStep === 4">
                <div class="card card-bordered card-preview">
                    <div class="card-inner">
                        <h5 class="card-title mb-4"><em class="icon ni ni-check-thick mr-1"></em> Review & Submit</h5>
                        <div class="alert alert-info">Please review the details below before saving.</div>
                        <div class="row gy-4">
                            <div class="col-md-6">
                                <h6 class="text-primary border-bottom pb-2 mb-3">@{{ isOrganisation ? 'Organisation' : 'Personal' }} Information</h6>
                                <div class="d-flex align-items-center mb-2">
                                    <span class="text-soft w-50">Name</span>
                                    <span class="fw-medium">@{{ first_name }}</span>
                                </div>
                                <div v-if="!isOrganisation" class="d-flex align-items-center mb-2">
                                    <span class="text-soft w-50">Gender</span>
                                    <span class="fw-medium">@{{ gender }}</span>
                                </div>
                                <div v-if="!isOrganisation" class="d-flex align-items-center mb-2">
                                    <span class="text-soft w-50">Date of Birth</span>
                                    <span class="fw-medium">@{{ dob }}</span>
                                </div>
                                <div v-if="isOrganisation" class="d-flex align-items-center mb-2">
                                    <span class="text-soft w-50">Reg Number</span>
                                    <span class="fw-medium">@{{ registration_number }}</span>
                                </div>
                                <div v-if="isOrganisation" class="d-flex align-items-center mb-2">
                                    <span class="text-soft w-50">Reg Date</span>
                                    <span class="fw-medium">@{{ registration_date }}</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-primary border-bottom pb-2 mb-3">Contact & Location</h6>
                                <div class="d-flex align-items-center mb-2">
                                    <span class="text-soft w-50">Telephone</span>
                                    <span class="fw-medium">@{{ mobile || '—' }}</span>
                                </div>
                                <div class="d-flex align-items-center mb-2">
                                    <span class="text-soft w-50">Email</span>
                                    <span class="fw-medium">@{{ email || '—' }}</span>
                                </div>
                                <div class="d-flex align-items-center mb-2">
                                    <span class="text-soft w-50">Address</span>
                                    <span class="fw-medium">@{{ address || '—' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer border-top d-flex justify-content-between">
                        <button type="button" class="btn btn-outline-secondary" @click="currentStep = 3">
                            <em class="icon ni ni-arrow-left mr-1"></em> Back
                        </button>
                        <button type="submit" class="btn btn-success">
                            <em class="icon ni ni-save mr-1"></em> {{trans_choice('core::general.save',1)}}
                        </button>
                    </div>
                </div>
            </div>

        </form>
    </div>
@endsection
@section('scripts')
    <script>
        var app = new Vue({
            el: "#app",
            data: {
                currentStep: 1,
                client_types: @json($client_types),
                client_type_id: parseInt("{{ old('client_type_id', $client->client_type_id) }}"),
                branch_id: parseInt("{{old('branch_id',$client->branch_id)}}"),
                external_id: "{{old('external_id',$client->external_id)}}",
                title_id: "{{old('title_id',$client->title_id)}}",
                first_name: "{{old('first_name',$client->first_name)}}",
                last_name: "{{old('last_name',$client->last_name)}}",
                gender: "{{old('gender',$client->gender)}}",
                marital_status: "{{old('marital_status',$client->marital_status)}}",
                country_id: parseInt("{{old('country_id',$client->country_id)}}"),
                mobile: "{{old('mobile',$client->mobile)}}",
                alternative_mobile: "{{old('alternative_mobile',$client->alternative_mobile)}}",
                email: "{{old('email',$client->email)}}",
                dob: "{{old('dob',$client->dob)}}",
                loan_officer_id: parseInt("{{old('loan_officer_id',$client->loan_officer_id)}}"),
                profession_id: parseInt("{{old('profession_id',$client->profession_id)}}"),
                address: `{{old('address',$client->address)}}`,
                postal_code: `{{old('postal_code',$client->zip)}}`,
                landing_site: `{{old('landing_site',$client->landing_site)}}`,
                years_in_business: `{{old('years_in_business',$client->years_in_business)}}`,
                notes: `{{old('notes',$client->notes)}}`,
                created_date: "{{old('created_date',$client->created_date)}}",
                
                // Individual fields
                id_number: "{{old('id_number',$client->id_number)}}",
                passport_number: "{{old('passport_number',$client->passport_number)}}",
                employment_status: "{{old('employment_status',$client->employment_status)}}",
                employer_name: "{{old('employer_name',$client->employer)}}",
                workplace_address: "{{old('workplace_address',$client->workplace_address)}}",
                employer_phone: "{{old('employer_phone',$client->employer_phone)}}",
                occupation: "{{old('occupation',$client->occupation)}}",
                
                next_of_kin: @json($client->next_of_kins->map(function($nok) {
                    return [
                        'full_name' => $nok->first_name . ' ' . $nok->last_name,
                        'id_number' => $nok->id_number,
                        'phone_number' => $nok->mobile,
                        'relationship' => optional($nok->client_relationship)->name
                    ];
                })),
                
                // Organisation fields
                registration_number: "{{old('registration_number',$client->registration_number)}}",
                registration_date: "{{old('registration_date',$client->registration_date ? $client->registration_date->toDateString() : '')}}",
                kra_pin: "{{old('kra_pin',$client->kra_pin)}}",
                nature_of_business: "{{old('nature_of_business',$client->nature_of_business)}}",
                business_category: "{{old('business_category',$client->business_category)}}",
                membership_number: "{{old('membership_number',$client->membership_number)}}",
                date_of_admission: "{{old('date_of_admission',$client->date_of_admission ? $client->date_of_admission->toDateString() : '')}}",
                
                directors: @json($client->directors),
                witnesses: @json($client->witnesses ?? [
                    ['name' => '', 'position' => '', 'signature' => ''],
                    ['name' => '', 'position' => '', 'signature' => '']
                ]),
                sacco_group_id: "{{ old('sacco_group_id', $sacco_group_id) }}",
            },
            watch: {
                first_name(val) {
                    if (!this.isOrganisation) {
                        let parts = val.trim().split(' ');
                        if (parts.length > 1) {
                            this.last_name = parts.pop();
                        } else {
                            this.last_name = 'Customer';
                        }
                    } else {
                        this.last_name = '';
                    }
                }
            },
            computed: {
                isOrganisation() {
                    let type = this.client_types.find(t => t.id == this.client_type_id);
                    if (!type) return false;
                    return type.name.toLowerCase() !== 'individual' && type.name.toLowerCase() !== 'customer';
                },
                canProceedStep1() {
                    if (this.isOrganisation) {
                        return this.first_name && this.registration_number && this.registration_date;
                    }
                    return this.first_name && this.gender && this.dob;
                },
                canProceedStep3() {
                    return this.branch_id && this.created_date;
                },
            },
            methods: {
                goToStep(step) {
                    this.currentStep = step;
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                },
                addNextOfKin() {
                    this.next_of_kin.push({ full_name: '', id_number: '', phone_number: '', relationship: '' });
                },
                removeNextOfKin(index) {
                    this.next_of_kin.splice(index, 1);
                },
                addDirector() {
                    this.directors.push({ full_name: '', id_number: '', passport_number: '', kra_pin: '', phone_number: '', position: '' });
                },
                removeDirector(index) {
                    this.directors.splice(index, 1);
                }
            },
        });
    </script>
@endsection