@extends('core::layouts.auth')
@section('title')
    {{ trans_choice('user::general.onboarding_step_identity', 1) }}
@endsection

@section('content')
    <div class="login-box" style="width: 480px;">
        <div class="login-logo">
            <a href="{{ url('/') }}" class="logo-link text-center">
                @if(!empty($logo=\Modules\Setting\Entities\Setting::where('setting_key','core.company_logo')->first()->setting_value))
                    <img class="logo-light logo-img logo-img-lg" src="{{ asset('storage/uploads/'.$logo) }}"
                         srcset="{{ asset('storage/uploads/'.$logo) }} 2x" alt="logo">
                @else
                    <h4>{{ \Modules\Setting\Entities\Setting::where('setting_key','core.company_name')->first()->setting_value }}</h4>
                @endif
            </a>
        </div>
        <div class="card">
            <div class="card-body login-card-body">
                @include('user::themes.adminlte.auth.onboarding._stepper', ['currentStep' => 2])
                <p class="login-box-msg">{{ trans_choice('user::general.onboarding_identity_msg', 1) }}</p>
                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif
                <form method="post" action="{{ url('register/identity') }}">
                    @csrf
                    <div class="form-group">
                        <label for="dob">{{ trans_choice('user::general.dob', 1) }}</label>
                        <input type="date" name="dob" id="dob"
                               class="form-control @error('dob') is-invalid @enderror"
                               value="{{ old('dob', $client->dob) }}" required>
                        @error('dob')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>
                    <div class="form-group">
                        <label for="national_id">{{ trans_choice('user::general.national_id', 1) }}</label>
                        <input type="text" name="national_id" id="national_id"
                               class="form-control @error('national_id') is-invalid @enderror"
                               value="{{ old('national_id') }}" required>
                        @error('national_id')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>
                    @if($identificationTypes->isNotEmpty())
                    <div class="form-group">
                        <label for="client_identification_type_id">{{ trans_choice('user::general.id_type', 1) }}</label>
                        <select name="client_identification_type_id" id="client_identification_type_id" class="form-control">
                            <option value="">{{ trans_choice('core::general.select', 1) }}</option>
                            @foreach($identificationTypes as $type)
                                <option value="{{ $type->id }}" {{ old('client_identification_type_id') == $type->id ? 'selected' : '' }}>{{ $type->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                    <div class="form-group">
                        <label for="kra_pin">{{ trans_choice('user::general.kra_pin', 1) }}</label>
                        <input type="text" name="kra_pin" id="kra_pin"
                               class="form-control @error('kra_pin') is-invalid @enderror"
                               value="{{ old('kra_pin', $client->kra_pin ?? '') }}">
                        @error('kra_pin')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>
                    <div class="form-group">
                        <label for="address">{{ trans_choice('user::general.address', 1) }}</label>
                        <textarea name="address" id="address" rows="2"
                                  class="form-control @error('address') is-invalid @enderror">{{ old('address', $client->address) }}</textarea>
                        @error('address')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>
                    <button type="submit" class="btn btn-lg btn-primary btn-block">
                        {{ trans_choice('user::general.onboarding_continue', 1) }}
                    </button>
                </form>
            </div>
        </div>
    </div>
@endsection
