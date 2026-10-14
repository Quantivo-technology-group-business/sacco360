@extends('core::layouts.master')
@section('title')
    {{ $user->first_name }} {{ $user->last_name }}
@endsection
@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-6">
                    <h1 class="mb-0">
                        {{ __('user::general.Profile') }}
                        <a href="#" onclick="window.history.back()"
                           class="btn btn-sm btn-outline-secondary ml-2 d-none d-sm-inline-flex">
                            <i class="fas fa-arrow-left mr-1"></i>{{ trans_choice('core::general.back', 1) }}
                        </a>
                    </h1>
                    <p class="text-muted mb-0 mt-1">{{ __('user::general.Account Information') }}</p>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right mb-0">
                        <li class="breadcrumb-item"><a href="{{ url('dashboard') }}">{{ trans_choice('dashboard::general.dashboard', 1) }}</a></li>
                        <li class="breadcrumb-item"><a href="{{ url('user/profile') }}">{{ __('user::general.Profile') }}</a></li>
                        <li class="breadcrumb-item active">{{ __('user::general.Account Information') }}</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content" id="app">
        <div class="row">
            <div class="col-lg-3 col-md-4 mb-3 mb-md-0">
                @include('user::themes.adminlte.user.profile.user_profile_menu')
            </div>
            <div class="col-lg-9 col-md-8">
                <form method="post" action="{{ url('user/profile/update_profile') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="card profile-form-card">
                        <div class="card-header profile-form-card__header">
                            <div>
                                <h4 class="mb-0">{{ __('user::general.Account Information') }}</h4>
                                <small class="text-muted">Update your personal details and contact information</small>
                            </div>
                        </div>
                        <div class="card-body">
                            {{-- Photo upload --}}
                            <div class="profile-form-section profile-photo-section mb-4">
                                <label class="profile-form-section__label">{{ trans_choice('user::general.photo', 1) }}</label>
                                <div class="profile-photo-upload">
                                    <div class="profile-photo-upload__preview" id="photo_preview">
                                        @if(!empty($user->photo))
                                            <img src="{{ asset('storage/uploads/' . $user->photo) }}" alt="Profile photo">
                                        @else
                                            <span class="profile-photo-upload__initials">{{ strtoupper(substr($user->first_name, 0, 1) . substr($user->last_name, 0, 1)) }}</span>
                                        @endif
                                    </div>
                                    <div class="profile-photo-upload__controls">
                                        <p class="mb-2 text-muted small">JPG, PNG or GIF. Max 2MB recommended.</p>
                                        <label for="photo" class="btn btn-outline-primary btn-sm mb-0">
                                            <i class="fas fa-camera mr-1"></i> Choose photo
                                        </label>
                                        <input type="file" name="photo" id="photo" class="d-none" accept="image/*"
                                               onchange="profilePreviewPhoto(this)">
                                        @error('photo')
                                        <div class="text-danger small mt-2">{{ $message }}</div>
                                        @enderror
                                        @include('core::partials.upload_template', ['templateKey' => 'photo.profile'])
                                    </div>
                                </div>
                            </div>

                            {{-- Personal details --}}
                            <div class="profile-form-section">
                                <h5 class="profile-form-section__title"><i class="fas fa-user mr-2"></i>Personal details</h5>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="first_name">{{ trans('user::general.first_name') }}</label>
                                            <input type="text" name="first_name" id="first_name" v-model="first_name"
                                                   class="form-control @error('first_name') is-invalid @enderror" required>
                                            @error('first_name')<span class="invalid-feedback">{{ $message }}</span>@enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="last_name">{{ trans('user::general.last_name') }}</label>
                                            <input type="text" name="last_name" id="last_name" v-model="last_name"
                                                   class="form-control @error('last_name') is-invalid @enderror" required>
                                            @error('last_name')<span class="invalid-feedback">{{ $message }}</span>@enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="gender">{{ trans('user::general.gender') }}</label>
                                            <select name="gender" id="gender" v-model="gender"
                                                    class="form-control @error('gender') is-invalid @enderror">
                                                <option value="male">{{ trans_choice('user::general.male', 1) }}</option>
                                                <option value="female">{{ trans_choice('user::general.female', 1) }}</option>
                                            </select>
                                            @error('gender')<span class="invalid-feedback">{{ $message }}</span>@enderror
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Contact --}}
                            <div class="profile-form-section">
                                <h5 class="profile-form-section__title"><i class="fas fa-envelope mr-2"></i>Contact information</h5>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="email">{{ trans_choice('user::general.email', 1) }}</label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text"><i class="fas fa-at"></i></span>
                                                </div>
                                                <input type="email" name="email" id="email" v-model="email"
                                                       class="form-control @error('email') is-invalid @enderror" required>
                                                @error('email')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="phone">{{ trans('user::general.phone') }}</label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                                </div>
                                                <input type="text" name="phone" id="phone" v-model="phone"
                                                       class="form-control @error('phone') is-invalid @enderror"
                                                       placeholder="e.g. 0712345678">
                                                @error('phone')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="form-group mb-0">
                                            <label for="address">{{ trans('user::general.address') }}</label>
                                            <textarea name="address" id="address" v-model="address" rows="3"
                                                      class="form-control @error('address') is-invalid @enderror"
                                                      placeholder="Street, city, country"></textarea>
                                            @error('address')<span class="invalid-feedback">{{ $message }}</span>@enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer profile-form-card__footer">
                            <a href="{{ url('dashboard') }}" class="btn btn-light">{{ trans_choice('core::general.cancel', 1) }}</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save mr-1"></i>{{ trans_choice('core::general.save', 1) }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </section>
@endsection
@section('scripts')
    <script>
        function profilePreviewPhoto(input) {
            if (!input.files || !input.files[0]) return;
            var reader = new FileReader();
            reader.onload = function (e) {
                var preview = document.getElementById('photo_preview');
                preview.innerHTML = '<img src="' + e.target.result + '" alt="Preview">';
            };
            reader.readAsDataURL(input.files[0]);
        }

        var app = new Vue({
            el: '#app',
            data: {
                first_name: @json(old('first_name', $user->first_name)),
                last_name: @json(old('last_name', $user->last_name)),
                phone: @json(old('phone', $user->phone)),
                email: @json(old('email', $user->email)),
                gender: @json(old('gender', $user->gender)),
                address: @json(old('address', $user->address)),
            }
        });
    </script>
@endsection
