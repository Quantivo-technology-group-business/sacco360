@extends('core::layouts.auth')
@section("title")
    {{trans_choice("user::general.login",1)}}
@endsection
@section('body_class', 'hold-transition auth-split-page')
@section('styles')
    <link rel="stylesheet" href="{{ asset('themes/adminlte/css/auth-login.css') }}"/>
@endsection
@section('content')
    <div class="auth-split-screen">
        {{-- Left: Hero slider --}}
        <div class="auth-slider-panel">
            <div id="loginCarousel" class="carousel slide carousel-fade h-100" data-ride="carousel" data-interval="5000" data-pause="false" data-wrap="true">
                <ol class="carousel-indicators auth-carousel-indicators">
                    <li data-target="#loginCarousel" data-slide-to="0" class="active"></li>
                    <li data-target="#loginCarousel" data-slide-to="1"></li>
                    <li data-target="#loginCarousel" data-slide-to="2"></li>
                </ol>
                <div class="carousel-inner h-100">
                    <div class="carousel-item active h-100">
                        <div class="auth-slide auth-slide-1">
                            <div class="auth-slide-content">
                                <div class="auth-slide-icon"><i class="fas fa-hand-holding-usd"></i></div>
                                <h2>Smart Loan Management</h2>
                                <p>Track applications, disbursements, and repayments in one unified platform built for SACCOs.</p>
                            </div>
                        </div>
                    </div>
                    <div class="carousel-item h-100">
                        <div class="auth-slide auth-slide-2">
                            <div class="auth-slide-content">
                                <div class="auth-slide-icon"><i class="fas fa-piggy-bank"></i></div>
                                <h2>Savings &amp; Deposits</h2>
                                <p>Empower members with flexible savings products, fixed deposits, and real-time account balances.</p>
                            </div>
                        </div>
                    </div>
                    <div class="carousel-item h-100">
                        <div class="auth-slide auth-slide-3">
                            <div class="auth-slide-content">
                                <div class="auth-slide-icon"><i class="fas fa-shield-alt"></i></div>
                                <h2>Secure &amp; Compliant</h2>
                                <p>Bank-grade security with full regulatory reporting to keep your cooperative running smoothly.</p>
                            </div>
                        </div>
                    </div>
                </div>
                <a class="carousel-control-prev auth-carousel-control" href="#loginCarousel" role="button" data-slide="prev">
                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                    <span class="sr-only">Previous</span>
                </a>
                <a class="carousel-control-next auth-carousel-control" href="#loginCarousel" role="button" data-slide="next">
                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                    <span class="sr-only">Next</span>
                </a>
            </div>
            <div class="auth-slider-brand">
                <span>{{ $company_name ?? 'Sacco Management Solutions' }}</span>
            </div>
        </div>

        {{-- Right: Login form --}}
        <div class="auth-form-panel">
            <div class="login-box auth-login-box">
                <div class="login-logo">
                    <a href="{{url('/')}}" class="logo-link text-center">
                        @if(!empty($logo=\Modules\Setting\Entities\Setting::where('setting_key','core.company_logo')->first()->setting_value))
                            <img class="logo-light logo-img logo-img-lg" src="{{asset('storage/uploads/'.$logo)}}"
                                 srcset="{{asset('storage/uploads/'.$logo)}} 2x"
                                 alt="logo">
                        @else
                            <h4>{{\Modules\Setting\Entities\Setting::where('setting_key','core.company_name')->first()->setting_value}}</h4>
                        @endif
                    </a>
                </div>
                <div class="card auth-login-card">
                    <div class="card-body login-card-body">
                        <p class="login-box-msg">{{trans_choice("user::general.login_msg",1)}}</p>

                        <form method="post" action="{{ route('login') }}">
                            {{csrf_field()}}
                            <div class="form-group">
                                <div class="form-label-group">
                                    <label class="form-label" for="email">{{trans_choice("user::general.email",1)}}</label>
                                </div>
                                <div class="auth-input-wrap">
                                    <i class="fas fa-envelope auth-input-icon"></i>
                                    <input type="email" class="form-control form-control-lg auth-input @error('email') is-invalid @enderror"
                                           name="email"
                                           placeholder="{{trans_choice("user::general.email",1)}}" value="{{ old('email') }}"
                                           required
                                           autocomplete="email" id="email" autofocus>
                                </div>
                                @error('email')
                                <span class="invalid-feedback d-block" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>
                            <div class="form-group">
                                <div class="form-label-group d-flex justify-content-between align-items-center">
                                    <label class="form-label mb-0"
                                           for="password">{{trans_choice("user::general.password",1)}}</label>
                                    <a class="link link-primary link-sm" tabindex="-1" href="{{ route('password.request') }}">
                                        {{trans_choice("user::general.forgot_password",1)}}
                                    </a>
                                </div>
                                <div class="form-control-wrap auth-input-wrap">
                                    <i class="fas fa-lock auth-input-icon"></i>
                                    <a tabindex="-1" href="#" class="form-icon form-icon-right passcode-switch auth-pass-toggle"
                                       data-target="password">
                                        <em class="passcode-icon icon-show icon ni ni-eye"></em>
                                        <em class="passcode-icon icon-hide icon ni ni-eye-off"></em>
                                    </a>
                                    <input type="password" name="password"
                                           class="form-control form-control-lg auth-input @error('password') is-invalid @enderror"
                                           placeholder="{{trans_choice("user::general.password",1)}}" required
                                           autocomplete="password" id="password">
                                </div>
                                @error('password')
                                <span class="invalid-feedback d-block" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>
                            <div class="form-group">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" name="remember"
                                           {{ old('remember') ? 'checked' : '' }} id="remember">
                                    <label class="custom-control-label"
                                           for="remember">{{trans_choice("user::general.remember_me",1)}}</label>
                                </div>
                            </div>
                            <div class="form-group mb-0">
                                <button class="btn btn-lg btn-primary btn-block auth-login-btn">
                                    <i class="fas fa-sign-in-alt mr-2"></i>{{trans_choice("user::general.login",1)}}
                                </button>
                            </div>
                        </form>
                        @if(optional(\Modules\Setting\Entities\Setting::where('setting_key','user.enable_registration')->first())->setting_value === 'yes')
                            <p class="text-center mt-3 mb-0">
                                <a href="{{ route('register') }}">{{trans_choice("user::general.register_msg",1)}}</a>
                            </p>
                        @endif
                        <div class="auth-form-trust">
                            <i class="fas fa-lock"></i>
                            <span>Secure 256-bit encrypted connection</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('scripts')
    <script>
        $(function () {
            var $carousel = $('#loginCarousel');
            var interval = 5000;

            $carousel.carousel({
                interval: interval,
                pause: false,
                wrap: true,
                ride: 'carousel'
            });

            function resetIndicatorProgress() {
                $('.auth-carousel-indicators li.active').each(function () {
                    var el = this;
                    el.classList.remove('active');
                    void el.offsetWidth;
                    el.classList.add('active');
                });
            }

            $carousel.on('slid.bs.carousel', function () {
                resetIndicatorProgress();
            });

            resetIndicatorProgress();
        });
    </script>
@endsection
