@extends('core::layouts.public')
@section('title')
    {{ $company_name }}
@endsection
@section('body_class', 'financial-page')
@section('styles')
    <link rel="stylesheet" href="{{ asset('themes/adminlte/css/financial-home.css') }}"/>
    <style>
        :root {
            --sacco-primary: {{ $homepage['primary_color'] }};
            --sacco-accent:  {{ $homepage['accent_color'] }};
            --sacco-primary-dark: color-mix(in srgb, {{ $homepage['primary_color'] }} 80%, #000);
        }
    </style>
@endsection

@section('content')
{{-- ══════════════════════════════════════════════════════════════
     STICKY NAV
══════════════════════════════════════════════════════════════ --}}
<nav class="fin-nav" id="finNav">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <a class="navbar-brand d-flex align-items-center" href="{{ url('/') }}">
                @if(!empty($company_logo))
                    <img src="{{ asset('storage/uploads/'.$company_logo) }}" alt="{{ $company_name }}" style="max-height:40px;margin-right:.75rem;">
                @endif
                <span>{{ $company_name }}</span>
            </a>
            <div class="d-flex align-items-center flex-wrap" style="gap:.35rem;margin-top:.25rem;">
                <a href="#products"  class="nav-link-btn d-none d-md-inline">Products</a>
                <a href="#services"  class="nav-link-btn d-none d-md-inline">Services</a>
                <a href="#about"     class="nav-link-btn d-none d-md-inline">About</a>
                <a href="#channels"  class="nav-link-btn d-none d-md-inline">Digital</a>
                <a href="#contact"   class="nav-link-btn d-none d-md-inline">Contact</a>
                <a href="{{ url('login') }}" class="fin-btn-outline btn-sm">
                    {{ trans_choice('core::general.staff_login', 1) }}
                </a>
                <a href="{{ url('login') }}" class="fin-btn-gold btn-sm">
                    {{ trans_choice('core::general.member_portal', 1) }}
                </a>
            </div>
        </div>
    </div>
</nav>

{{-- ══════════════════════════════════════════════════════════════
     HERO — split: carousel left, loan calculator right
══════════════════════════════════════════════════════════════ --}}
<section class="fin-hero fin-hero--split">
    <div class="fin-hero-carousel-col">
        <div id="heroCarousel" class="carousel slide carousel-fade h-100" data-ride="carousel" data-interval="6000">
            <ol class="carousel-indicators">
                <li data-target="#heroCarousel" data-slide-to="0" class="active"></li>
                <li data-target="#heroCarousel" data-slide-to="1"></li>
                <li data-target="#heroCarousel" data-slide-to="2"></li>
            </ol>
            <div class="carousel-inner h-100">
                <div class="carousel-item active h-100">
                    <div class="fin-hero-slide fin-hero-slide-1 h-100">
                        <div class="container-fluid">
                            <div class="fin-hero-content">
                                @if(!empty($homepage['license_number']))
                                    <div class="fin-hero-badge"><i class="fas fa-shield-alt"></i> {{ trans_choice('sacco::general.sasra_licensed', 1) }} — {{ $homepage['license_number'] }}</div>
                                @else
                                    <div class="fin-hero-badge"><i class="fas fa-university"></i> Trusted Financial Partner</div>
                                @endif
                                <h1>{{ $company_name }}</h1>
                                <p class="lead">{{ $homepage['tagline'] ?: trans_choice('core::general.homepage_tagline', 1) }}</p>
                                <div class="d-flex flex-wrap" style="gap:.6rem;">
                                    <a href="{{ url('login') }}" class="fin-btn-gold btn btn-lg">
                                        <i class="fas fa-sign-in-alt mr-1"></i> {{ trans_choice('core::general.access_portal', 1) }}
                                    </a>
                                    <a href="#products" class="btn btn-outline-light btn-lg">Explore Products</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="carousel-item h-100">
                    <div class="fin-hero-slide fin-hero-slide-2 h-100">
                        <div class="container-fluid">
                            <div class="fin-hero-content">
                                <div class="fin-hero-badge"><i class="fas fa-piggy-bank"></i> Member Savings</div>
                                <h1>Grow Your Wealth Together</h1>
                                <p class="lead">Build share capital through BOSA savings and flexible FOSA accounts with competitive returns and member dividends.</p>
                                <a href="#products" class="fin-btn-gold btn btn-lg"><i class="fas fa-arrow-right mr-1"></i> Explore Products</a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="carousel-item h-100">
                    <div class="fin-hero-slide fin-hero-slide-3 h-100">
                        <div class="container-fluid">
                            <div class="fin-hero-content">
                                <div class="fin-hero-badge"><i class="fas fa-mobile-alt"></i> Digital Banking</div>
                                <h1>Bank Anytime, Anywhere</h1>
                                <p class="lead">Access accounts, apply for loans, and make payments via M-Pesa, USSD {{ $homepage['ussd_shortcode'] }}, and our secure member portal.</p>
                                <a href="{{ url('login') }}" class="fin-btn-gold btn btn-lg"><i class="fas fa-laptop mr-1"></i> {{ trans_choice('sacco::general.internet_banking', 1) }}</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Loan calculator panel --}}
    <div class="fin-hero-calc-col">
        <div class="fin-calc-card">
            <div class="fin-calc-header">
                <i class="fas fa-calculator"></i>
                <span>Loan Eligibility Calculator</span>
            </div>
            <div class="fin-calc-body">
                <div class="fin-calc-group">
                    <label for="calcAmount">Loan Amount (KES)</label>
                    <div class="fin-calc-input-wrap">
                        <span class="fin-calc-prefix">KES</span>
                        <input type="number" id="calcAmount" class="fin-calc-input" placeholder="100,000" min="1000" step="1000" value="100000">
                    </div>
                </div>
                <div class="fin-calc-group">
                    <label for="calcMonths">Repayment Period</label>
                    <div class="fin-calc-slider-wrap">
                        <input type="range" id="calcMonths" class="fin-calc-range" min="1" max="60" value="12">
                        <div class="fin-calc-slider-labels">
                            <span>1 mo</span><span id="calcMonthsVal" class="fin-calc-slider-val">12 months</span><span>60 mo</span>
                        </div>
                    </div>
                </div>
                <div class="fin-calc-group">
                    <label for="calcRate">Interest Rate (% p.a.)</label>
                    <div class="fin-calc-input-wrap">
                        <input type="number" id="calcRate" class="fin-calc-input" placeholder="12" min="1" max="50" step="0.5" value="12">
                        <span class="fin-calc-suffix">%</span>
                    </div>
                </div>
                <div class="fin-calc-result" id="calcResult">
                    <div class="fin-calc-result-row">
                        <span>Monthly Repayment</span>
                        <strong id="calcMonthly">KES 8,885</strong>
                    </div>
                    <div class="fin-calc-result-row">
                        <span>Total Repayment</span>
                        <strong id="calcTotal">KES 106,620</strong>
                    </div>
                    <div class="fin-calc-result-row">
                        <span>Total Interest</span>
                        <strong id="calcInterest" style="color:var(--fin-accent)">KES 6,620</strong>
                    </div>
                </div>
                <a href="{{ url('login') }}" class="fin-calc-cta">
                    <i class="fas fa-paper-plane mr-2"></i>Apply for This Loan
                </a>
                <p class="fin-calc-note">* Estimates only. Actual rates depend on membership standing.</p>
            </div>
        </div>
    </div>
</section>

{{-- ══════════════════════════════════════════════════════════════
     TRUST / LIVE STATS BAR
══════════════════════════════════════════════════════════════ --}}
<div class="fin-trust-bar">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-sm-3 col-6 mb-2 mb-sm-0">
                <div class="fin-trust-item">
                    <i class="fas fa-lock"></i>
                    <span>256-bit SSL Encrypted</span>
                </div>
            </div>
            <div class="col-sm-3 col-6 mb-2 mb-sm-0">
                <div class="fin-trust-item">
                    <i class="fas fa-users"></i>
                    <span>Member-Owned Cooperative</span>
                </div>
            </div>
            <div class="col-sm-3 col-6">
                <div class="fin-trust-item">
                    <i class="fas fa-headset"></i>
                    <span>{{ $homepage['office_hours'] ?: 'Mon – Fri: 8:00 AM – 5:00 PM' }}</span>
                </div>
            </div>
            <div class="col-sm-3 col-6">
                <div class="fin-trust-item">
                    <i class="fas fa-award"></i>
                    @if(!empty($homepage['license_number']))
                        <span>Licensed: {{ $homepage['license_number'] }}</span>
                    @else
                        <span>Regulated &amp; Compliant</span>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════
     QUICK ACCESS CARDS
══════════════════════════════════════════════════════════════ --}}
<div class="container fin-quick-access">
    <div class="row">
        <div class="col-md-3 mb-3">
            <div class="fin-quick-card">
                <div class="fin-quick-icon"><i class="fas fa-phone-alt"></i></div>
                <h6>{{ trans_choice('core::general.homepage_quick_ussd', 1) }}</h6>
                <p>{{ trans_choice('sacco::general.dial_ussd', 1) }} <strong>{{ $homepage['ussd_shortcode'] }}</strong></p>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="fin-quick-card">
                <div class="fin-quick-icon"><i class="fas fa-mobile-alt"></i></div>
                <h6>{{ trans_choice('core::general.homepage_quick_paybill', 1) }}</h6>
                <p>
                    @if(!empty($homepage['paybill_number']))
                        PayBill: <strong>{{ $homepage['paybill_number'] }}</strong>
                    @else
                        {{ trans_choice('sacco::general.mpesa_deposit_instructions', 1) }}
                    @endif
                </p>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="fin-quick-card">
                <div class="fin-quick-icon"><i class="fas fa-laptop"></i></div>
                <h6>{{ trans_choice('core::general.homepage_quick_portal', 1) }}</h6>
                <p>
                    <a href="{{ url('login') }}">{{ trans_choice('sacco::general.internet_banking', 1) }}</a><br>
                    <a href="{{ url('login') }}">My Accounts</a> ·
                    <a href="{{ url('login') }}">Apply for Loan</a>
                </p>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="fin-quick-card">
                <div class="fin-quick-icon"><i class="fas fa-envelope-open-text"></i></div>
                <h6>Need Help?</h6>
                <p>
                    @if(!empty($company_email))
                        <a href="mailto:{{ $company_email }}">{{ $company_email }}</a><br>
                    @endif
                    @if(!empty($company_phone))
                        {{ $company_phone }}
                    @endif
                </p>
            </div>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════
     LIVE STATS
══════════════════════════════════════════════════════════════ --}}
@if($homepage['member_count'] > 0 || $homepage['branch_count'] > 0 || $homepage['years_serving'])
<section class="fin-stats-bar">
    <div class="container">
        <div class="row justify-content-center text-center">
            @if($homepage['member_count'] > 0)
            <div class="col-6 col-md-3 fin-stat-bar-item">
                <h3 class="stat-counter" data-target="{{ $homepage['member_count'] }}">0</h3>
                <p>{{ trans_choice('core::general.homepage_stats_members', 1) }}</p>
            </div>
            @endif
            @if($homepage['branch_count'] > 0)
            <div class="col-6 col-md-3 fin-stat-bar-item">
                <h3 class="stat-counter" data-target="{{ $homepage['branch_count'] }}">0</h3>
                <p>{{ trans_choice('core::general.homepage_stats_branches', 1) }}</p>
            </div>
            @endif
            @if($homepage['years_serving'])
            <div class="col-6 col-md-3 fin-stat-bar-item">
                <h3 class="stat-counter" data-target="{{ $homepage['years_serving'] }}">0</h3>
                <p>{{ trans_choice('core::general.homepage_stats_years', 1) }}</p>
            </div>
            @endif
            <div class="col-6 col-md-3 fin-stat-bar-item">
                <h3>99<span style="font-size:1.5rem;">%</span></h3>
                <p>Member Satisfaction</p>
            </div>
        </div>
    </div>
</section>
@endif

{{-- ══════════════════════════════════════════════════════════════
     PRODUCT HIGHLIGHTS  (detailed cards with features list)
══════════════════════════════════════════════════════════════ --}}
<section class="fin-products" id="products">
    <div class="container">
        <div class="text-center mb-5">
            <span class="fin-section-label">Financial Solutions</span>
            <h2 class="fin-section-title">Products Built for Members</h2>
            <p class="fin-section-desc">{{ trans_choice('core::general.homepage_services_desc', 1) }}</p>
        </div>
        <div class="row">
            {{-- Savings --}}
            <div class="col-lg-4 mb-4">
                <div class="fin-product-card fin-product-card--detailed">
                    <div class="fin-product-card__top">
                        <div class="fin-product-icon"><i class="fas fa-piggy-bank"></i></div>
                        <div class="fin-product-badge">Popular</div>
                    </div>
                    <h4>Savings Accounts</h4>
                    <p class="fin-product-desc">BOSA share capital and FOSA deposit accounts designed to help members save consistently and earn dividends.</p>
                    <ul class="fin-product-features">
                        <li><i class="fas fa-check"></i> BOSA share capital accumulation</li>
                        <li><i class="fas fa-check"></i> FOSA flexible deposit accounts</li>
                        <li><i class="fas fa-check"></i> Competitive annual dividend</li>
                        <li><i class="fas fa-check"></i> No monthly maintenance fee</li>
                        <li><i class="fas fa-check"></i> M-Pesa deposit integration</li>
                    </ul>
                    <div class="fin-product-footer">
                        <div class="fin-product-meta">Competitive Returns</div>
                        <a href="{{ url('login') }}" class="fin-product-cta">Open Account <i class="fas fa-arrow-right ml-1"></i></a>
                    </div>
                </div>
            </div>

            {{-- Loans --}}
            <div class="col-lg-4 mb-4">
                <div class="fin-product-card fin-product-card--detailed featured">
                    <div class="fin-product-card__top">
                        <div class="fin-product-icon"><i class="fas fa-hand-holding-usd"></i></div>
                        <div class="fin-product-badge fin-product-badge--gold">Best Value</div>
                    </div>
                    <h4>Member Loans</h4>
                    <p class="fin-product-desc">Affordable credit backed by your savings and guarantors — emergency, development, and asset financing available.</p>
                    <ul class="fin-product-features">
                        <li><i class="fas fa-check"></i> Up to 3× your savings in credit</li>
                        <li><i class="fas fa-check"></i> Repay in up to 60 months</li>
                        <li><i class="fas fa-check"></i> Emergency loans within 24 hrs</li>
                        <li><i class="fas fa-check"></i> Development &amp; asset financing</li>
                        <li><i class="fas fa-check"></i> Low flat interest rate</li>
                    </ul>
                    <div class="fin-product-footer">
                        <div class="fin-product-meta">Low Interest Rates</div>
                        <a href="{{ url('login') }}" class="fin-product-cta fin-product-cta--light">Apply Now <i class="fas fa-arrow-right ml-1"></i></a>
                    </div>
                </div>
            </div>

            {{-- Investments --}}
            <div class="col-lg-4 mb-4">
                <div class="fin-product-card fin-product-card--detailed">
                    <div class="fin-product-card__top">
                        <div class="fin-product-icon"><i class="fas fa-chart-line"></i></div>
                        <div class="fin-product-badge">Grow Wealth</div>
                    </div>
                    <h4>Investments &amp; Shares</h4>
                    <p class="fin-product-desc">Own a stake in your SACCO through share capital and receive annual dividends on your investment.</p>
                    <ul class="fin-product-features">
                        <li><i class="fas fa-check"></i> Annual dividend declarations</li>
                        <li><i class="fas fa-check"></i> Share capital appreciation</li>
                        <li><i class="fas fa-check"></i> Member-owned governance</li>
                        <li><i class="fas fa-check"></i> Fixed deposits available</li>
                        <li><i class="fas fa-check"></i> AGM voting rights</li>
                    </ul>
                    <div class="fin-product-footer">
                        <div class="fin-product-meta">Annual Dividends</div>
                        <a href="{{ url('login') }}" class="fin-product-cta">Invest Now <i class="fas fa-arrow-right ml-1"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ══════════════════════════════════════════════════════════════
     ALL SERVICES GRID
══════════════════════════════════════════════════════════════ --}}
<section class="fin-services" id="services">
    <div class="container">
        <div class="text-center mb-5">
            <span class="fin-section-label">Full Suite</span>
            <h2 class="fin-section-title">{{ trans_choice('core::general.our_services', 1) }}</h2>
            <p class="fin-section-desc">Everything you need to manage your finances in one cooperative platform.</p>
        </div>
        <div class="row">
            @foreach($homepage['featured_services'] as $service)
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="fin-service-card">
                    <div class="fin-service-icon"><i class="{{ $service['icon'] ?? 'fas fa-star' }}"></i></div>
                    <h5>{{ $service['title'] }}</h5>
                    <p>{{ $service['description'] }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ══════════════════════════════════════════════════════════════
     WHY CHOOSE US
══════════════════════════════════════════════════════════════ --}}
<section class="fin-why">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-5 mb-4 mb-lg-0">
                <span class="fin-section-label">Why Members Choose Us</span>
                <h2 class="fin-section-title">Banking That Puts Members First</h2>
                <p class="text-muted mb-4">We're not a bank — we're a cooperative owned and governed by you. Every shilling deposited, every loan repaid, builds a stronger community.</p>
                <a href="{{ url('login') }}" class="fin-btn-outline btn mb-3 mr-2">
                    <i class="fas fa-user-plus mr-1"></i> Become a Member
                </a>
                <a href="#about" class="btn btn-link text-muted mb-3">Learn More &rarr;</a>
            </div>
            <div class="col-lg-7">
                <div class="row">
                    <div class="col-sm-6 mb-3">
                        <div class="fin-why-card">
                            <div class="fin-why-icon"><i class="fas fa-percentage"></i></div>
                            <h6>Competitive Rates</h6>
                            <p>Low, transparent interest rates on loans and high returns on savings — no hidden fees.</p>
                        </div>
                    </div>
                    <div class="col-sm-6 mb-3">
                        <div class="fin-why-card">
                            <div class="fin-why-icon"><i class="fas fa-bolt"></i></div>
                            <h6>Fast Approvals</h6>
                            <p>Emergency loans approved within 24 hours. Digital applications reviewed instantly.</p>
                        </div>
                    </div>
                    <div class="col-sm-6 mb-3">
                        <div class="fin-why-card">
                            <div class="fin-why-icon"><i class="fas fa-shield-alt"></i></div>
                            <h6>Fully Regulated</h6>
                            <p>Supervised by SASRA and compliant with all cooperative society regulations and reporting standards.</p>
                        </div>
                    </div>
                    <div class="col-sm-6 mb-3">
                        <div class="fin-why-card">
                            <div class="fin-why-icon"><i class="fas fa-hands-helping"></i></div>
                            <h6>Member Governance</h6>
                            <p>You vote, you decide. Annual General Meetings where every member has a say in our direction.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ══════════════════════════════════════════════════════════════
     ABOUT / MISSION / VISION
══════════════════════════════════════════════════════════════ --}}
<section class="fin-about" id="about">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-5 mb-5 mb-lg-0">
                <span class="fin-section-label">Who We Are</span>
                <h2 class="fin-section-title">{{ trans_choice('core::general.homepage_about_title', 1) }}</h2>
                <p class="text-muted mb-4">{{ trans_choice('core::general.homepage_about_desc', 1) }}</p>
                @if(!empty($homepage['about_text']))
                    <p class="mb-4">{{ $homepage['about_text'] }}</p>
                @endif
                @if(!empty($homepage['founded_year']))
                    <div class="fin-about-founded">
                        <i class="fas fa-calendar-alt"></i>
                        Established {{ $homepage['founded_year'] }} &bull; {{ $homepage['years_serving'] }} years of service
                    </div>
                @endif
                @if(!empty($homepage['registration_number']))
                    <div class="fin-about-founded mt-2">
                        <i class="fas fa-registered"></i>
                        Registration No: {{ $homepage['registration_number'] }}
                    </div>
                @endif
            </div>
            <div class="col-lg-7">
                @if(!empty($homepage['mission']))
                <div class="fin-about-box">
                    <div class="fin-about-box__icon"><i class="fas fa-bullseye"></i></div>
                    <div>
                        <h6>{{ trans_choice('core::general.homepage_mission_label', 1) }}</h6>
                        <p class="mb-0">{{ $homepage['mission'] }}</p>
                    </div>
                </div>
                @endif
                @if(!empty($homepage['vision']))
                <div class="fin-about-box">
                    <div class="fin-about-box__icon"><i class="fas fa-eye"></i></div>
                    <div>
                        <h6>{{ trans_choice('core::general.homepage_vision_label', 1) }}</h6>
                        <p class="mb-0">{{ $homepage['vision'] }}</p>
                    </div>
                </div>
                @endif
                <div class="fin-about-box">
                    <div class="fin-about-box__icon"><i class="fas fa-handshake"></i></div>
                    <div>
                        <h6>Our Values</h6>
                        <p class="mb-0">Integrity, transparency, member welfare, financial inclusion, and cooperative spirit drive every decision we make.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ══════════════════════════════════════════════════════════════
     HOW IT WORKS  (connected timeline)
══════════════════════════════════════════════════════════════ --}}
<section class="fin-steps">
    <div class="container">
        <div class="text-center mb-5">
            <span class="fin-section-label">Getting Started</span>
            <h2 class="fin-section-title">{{ trans_choice('sacco::general.how_it_works', 1) }}</h2>
            <p class="fin-section-desc">{{ trans_choice('core::general.homepage_how_it_works_desc', 1) }}</p>
        </div>
        <div class="fin-steps-timeline">
            <div class="fin-steps-line"></div>
            <div class="row justify-content-center">
                <div class="col-md-3 col-sm-6 mb-4">
                    <div class="fin-step-card">
                        <div class="fin-step-num">1</div>
                        <h5>{{ trans_choice('sacco::general.step_join', 1) }}</h5>
                        <p class="text-muted">{{ trans_choice('sacco::general.step_join_desc', 1) }}</p>
                        <div class="fin-step-detail">Fill the membership form, pay share capital, and receive your member number.</div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6 mb-4">
                    <div class="fin-step-card">
                        <div class="fin-step-num">2</div>
                        <h5>{{ trans_choice('sacco::general.step_save', 1) }}</h5>
                        <p class="text-muted">{{ trans_choice('sacco::general.step_save_desc', 1) }}</p>
                        <div class="fin-step-detail">Deposit via M-Pesa PayBill, bank transfer, or visit any of our branches.</div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6 mb-4">
                    <div class="fin-step-card">
                        <div class="fin-step-num">3</div>
                        <h5>{{ trans_choice('sacco::general.step_borrow', 1) }}</h5>
                        <p class="text-muted">{{ trans_choice('sacco::general.step_borrow_desc', 1) }}</p>
                        <div class="fin-step-detail">Apply online or in branch. Get up to 3× your savings with flexible repayment.</div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6 mb-4">
                    <div class="fin-step-card">
                        <div class="fin-step-num">4</div>
                        <h5>{{ trans_choice('sacco::general.step_grow', 1) }}</h5>
                        <p class="text-muted">{{ trans_choice('sacco::general.step_grow_desc', 1) }}</p>
                        <div class="fin-step-detail">Earn dividends annually, build your share capital, and grow your financial future.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ══════════════════════════════════════════════════════════════
     DIGITAL CHANNELS
══════════════════════════════════════════════════════════════ --}}
<section class="fin-channels" id="channels">
    <div class="container">
        <div class="text-center mb-5">
            <span class="fin-section-label">Digital Banking</span>
            <h2 class="fin-section-title">Bank on Your Terms</h2>
            <p class="fin-section-desc">Multiple channels so you can transact wherever, whenever — no branch visit required.</p>
        </div>
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="fin-channel-card">
                    <div class="fin-channel-icon fin-channel-icon--mpesa">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                    <h5>M-Pesa Integration</h5>
                    <p>Deposit to your FOSA account, repay loans, and check balances directly via M-Pesa PayBill.</p>
                    @if(!empty($homepage['paybill_number']))
                        <div class="fin-channel-detail">
                            <span class="fin-channel-label">PayBill Number</span>
                            <strong class="fin-channel-value">{{ $homepage['paybill_number'] }}</strong>
                        </div>
                    @endif
                    <div class="fin-channel-steps">
                        <div class="fin-channel-step"><span>1</span> M-Pesa → Lipa na M-Pesa → PayBill</div>
                        <div class="fin-channel-step"><span>2</span> Enter PayBill &amp; your member number</div>
                        <div class="fin-channel-step"><span>3</span> Enter amount &amp; M-Pesa PIN</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="fin-channel-card">
                    <div class="fin-channel-icon fin-channel-icon--ussd">
                        <i class="fas fa-phone-alt"></i>
                    </div>
                    <h5>USSD Banking</h5>
                    <p>Access your accounts without internet. Check balance, transfer funds, and apply for mini-loans.</p>
                    <div class="fin-channel-detail">
                        <span class="fin-channel-label">Dial</span>
                        <strong class="fin-channel-value">{{ $homepage['ussd_shortcode'] }}</strong>
                    </div>
                    <div class="fin-channel-steps">
                        <div class="fin-channel-step"><span>1</span> Dial {{ $homepage['ussd_shortcode'] }}</div>
                        <div class="fin-channel-step"><span>2</span> Enter your PIN</div>
                        <div class="fin-channel-step"><span>3</span> Select service from menu</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="fin-channel-card">
                    <div class="fin-channel-icon fin-channel-icon--portal">
                        <i class="fas fa-laptop"></i>
                    </div>
                    <h5>Member Portal</h5>
                    <p>Full account management, loan applications, statements, guarantor requests, and more — online 24/7.</p>
                    <div class="fin-channel-features">
                        <div class="fin-channel-feat"><i class="fas fa-check-circle"></i> View account balances</div>
                        <div class="fin-channel-feat"><i class="fas fa-check-circle"></i> Apply for loans online</div>
                        <div class="fin-channel-feat"><i class="fas fa-check-circle"></i> Download statements</div>
                        <div class="fin-channel-feat"><i class="fas fa-check-circle"></i> Manage guarantors</div>
                    </div>
                    <a href="{{ url('login') }}" class="fin-channel-btn">Login to Portal <i class="fas fa-arrow-right ml-1"></i></a>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ══════════════════════════════════════════════════════════════
     MEMBER TESTIMONIALS
══════════════════════════════════════════════════════════════ --}}
<section class="fin-testimonials">
    <div class="container">
        <div class="text-center mb-5">
            <span class="fin-section-label">Member Stories</span>
            <h2 class="fin-section-title">What Our Members Say</h2>
        </div>
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="fin-testimonial-card">
                    <div class="fin-testimonial-stars">
                        <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                    </div>
                    <p class="fin-testimonial-text">"The emergency loan process was incredibly fast. I applied online and had the funds in my account within 24 hours. This SACCO truly understands member needs."</p>
                    <div class="fin-testimonial-author">
                        <div class="fin-testimonial-avatar">JM</div>
                        <div>
                            <strong>Jane M.</strong>
                            <small>Member since 2019</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="fin-testimonial-card">
                    <div class="fin-testimonial-stars">
                        <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                    </div>
                    <p class="fin-testimonial-text">"I've been saving here for 6 years. The annual dividends have been consistent, and the staff are always helpful. I recently got a development loan at a very fair rate."</p>
                    <div class="fin-testimonial-author">
                        <div class="fin-testimonial-avatar">PO</div>
                        <div>
                            <strong>Peter O.</strong>
                            <small>Member since 2018</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="fin-testimonial-card">
                    <div class="fin-testimonial-stars">
                        <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star-half-alt"></i>
                    </div>
                    <p class="fin-testimonial-text">"The member portal makes everything so convenient. I can check my balance, apply for a loan, and download my statement without visiting the branch."</p>
                    <div class="fin-testimonial-author">
                        <div class="fin-testimonial-avatar">AW</div>
                        <div>
                            <strong>Alice W.</strong>
                            <small>Member since 2021</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ══════════════════════════════════════════════════════════════
     FAQ ACCORDION
══════════════════════════════════════════════════════════════ --}}
<section class="fin-faq">
    <div class="container">
        <div class="row">
            <div class="col-lg-5 mb-4 mb-lg-0">
                <span class="fin-section-label">FAQ</span>
                <h2 class="fin-section-title">Frequently Asked Questions</h2>
                <p class="text-muted mb-4">Can't find an answer? Reach us during office hours and we'll be happy to help.</p>
                @if(!empty($company_phone))
                <a href="tel:{{ preg_replace('/\s+/','',$company_phone) }}" class="fin-btn-outline btn">
                    <i class="fas fa-phone mr-1"></i> {{ $company_phone }}
                </a>
                @endif
                @if(!empty($company_email))
                <a href="mailto:{{ $company_email }}" class="btn btn-link text-muted d-block mt-2">{{ $company_email }}</a>
                @endif
            </div>
            <div class="col-lg-7">
                <div class="fin-faq-list" id="faqAccordion">
                    @php
                    $faqs = [
                        ['q'=>'Who can join the SACCO?', 'a'=>'Any individual who meets the membership criteria — typically employees of member organizations, residents of a defined geographic area, or those in a specific profession. Contact us to confirm eligibility.'],
                        ['q'=>'How much can I borrow?', 'a'=>'Members can borrow up to three (3) times their total savings/deposits, subject to guarantors and creditworthiness assessment. Use our loan calculator above for an estimate.'],
                        ['q'=>'How long does loan approval take?', 'a'=>'Standard loans are processed within 3–5 business days. Emergency loans are reviewed within 24 hours for qualifying members in good standing.'],
                        ['q'=>'How do I repay my loan?', 'a'=>'Repayments can be made via M-Pesa PayBill, salary check-off, bank standing order, or over-the-counter at any branch. You can also repay early with no penalty.'],
                        ['q'=>'What is BOSA vs FOSA?', 'a'=>'BOSA (Back Office Savings Activity) handles share capital and term deposits. FOSA (Front Office Savings Activity) is your transactional account for deposits, withdrawals, and payments — similar to a regular bank account.'],
                        ['q'=>'How are dividends calculated?', 'a'=>'Dividends are declared annually at the AGM based on member share capital held throughout the year. The rate varies by financial performance and is voted on by members.'],
                    ];
                    @endphp
                    @foreach($faqs as $i => $faq)
                    <div class="fin-faq-item">
                        <button class="fin-faq-q {{ $i === 0 ? 'active' : '' }}" data-target="faq{{ $i }}">
                            {{ $faq['q'] }}
                            <i class="fas fa-chevron-down fin-faq-chevron"></i>
                        </button>
                        <div class="fin-faq-a" id="faq{{ $i }}" style="{{ $i === 0 ? '' : 'display:none' }}">
                            {{ $faq['a'] }}
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ══════════════════════════════════════════════════════════════
     CONTACT / LOCATION
══════════════════════════════════════════════════════════════ --}}
<section class="fin-contact" id="contact">
    <div class="container">
        <div class="text-center mb-5">
            <span class="fin-section-label">Get in Touch</span>
            <h2 class="fin-section-title">Find Us</h2>
        </div>
        <div class="row">
            <div class="col-lg-5 mb-4 mb-lg-0">
                <div class="fin-contact-info">
                    @if(!empty($company_address))
                    <div class="fin-contact-row">
                        <div class="fin-contact-icon"><i class="fas fa-map-marker-alt"></i></div>
                        <div>
                            <h6>Office Address</h6>
                            <p>{{ $company_address }}</p>
                        </div>
                    </div>
                    @endif
                    @if(!empty($company_phone))
                    <div class="fin-contact-row">
                        <div class="fin-contact-icon"><i class="fas fa-phone"></i></div>
                        <div>
                            <h6>Phone</h6>
                            <p><a href="tel:{{ preg_replace('/\s+/','',$company_phone) }}">{{ $company_phone }}</a></p>
                        </div>
                    </div>
                    @endif
                    @if(!empty($company_email))
                    <div class="fin-contact-row">
                        <div class="fin-contact-icon"><i class="fas fa-envelope"></i></div>
                        <div>
                            <h6>Email</h6>
                            <p><a href="mailto:{{ $company_email }}">{{ $company_email }}</a></p>
                        </div>
                    </div>
                    @endif
                    <div class="fin-contact-row">
                        <div class="fin-contact-icon"><i class="fas fa-clock"></i></div>
                        <div>
                            <h6>Office Hours</h6>
                            <p>{{ $homepage['office_hours'] ?: 'Mon – Fri: 8:00 AM – 5:00 PM' }}</p>
                            <p class="text-muted" style="font-size:.8rem">Closed on weekends &amp; public holidays</p>
                        </div>
                    </div>
                    @if(!empty($homepage['license_number']) || !empty($homepage['registration_number']))
                    <div class="fin-contact-row">
                        <div class="fin-contact-icon"><i class="fas fa-certificate"></i></div>
                        <div>
                            <h6>Regulatory Info</h6>
                            @if(!empty($homepage['license_number']))
                            <p>License: {{ $homepage['license_number'] }}</p>
                            @endif
                            @if(!empty($homepage['registration_number']))
                            <p>Reg No: {{ $homepage['registration_number'] }}</p>
                            @endif
                        </div>
                    </div>
                    @endif
                </div>
            </div>
            <div class="col-lg-7">
                {{-- Map placeholder --}}
                <div class="fin-map-placeholder">
                    <div class="fin-map-inner">
                        <i class="fas fa-map-marked-alt"></i>
                        <p>{{ $company_name }}</p>
                        @if(!empty($company_address))
                            <span>{{ $company_address }}</span>
                        @endif
                        @if(!empty($company_phone))
                        <a href="tel:{{ preg_replace('/\s+/','',$company_phone) }}" class="fin-map-cta">
                            <i class="fas fa-directions mr-1"></i> Get Directions
                        </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ══════════════════════════════════════════════════════════════
     CALL TO ACTION BANNER
══════════════════════════════════════════════════════════════ --}}
<section class="fin-cta">
    <div class="container">
        <div class="fin-cta-inner">
            <div>
                <h3>{{ trans_choice('core::general.homepage_cta_title', 1) }}</h3>
                <p class="text-muted mb-0">{{ trans_choice('core::general.homepage_cta_desc', 1) }}</p>
            </div>
            <div class="fin-cta-actions">
                <a href="{{ url('login') }}" class="fin-btn-gold btn btn-lg">
                    {{ trans_choice('core::general.access_portal', 1) }}
                </a>
                @if(config('app.installed') && optional(\Modules\Setting\Entities\Setting::where('setting_key','user.enable_registration')->first())->setting_value === 'yes')
                <a href="{{ route('register') }}" class="fin-btn-outline btn btn-lg">
                    {{ trans_choice('user::general.register_msg', 1) }}
                </a>
                @endif
            </div>
        </div>
    </div>
</section>

{{-- ══════════════════════════════════════════════════════════════
     FOOTER
══════════════════════════════════════════════════════════════ --}}
<footer class="fin-footer">
    <div class="container">
        <div class="row">
            {{-- Brand column --}}
            <div class="col-lg-4 mb-4">
                @if(!empty($company_logo))
                    <img src="{{ asset('storage/uploads/'.$company_logo) }}" alt="{{ $company_name }}" style="max-height:40px;margin-bottom:1rem;">
                @else
                    <h5 style="font-weight:800;margin-bottom:1rem;">{{ $company_name }}</h5>
                @endif
                <p style="font-size:.87rem;opacity:.8;line-height:1.7;max-width:280px;">
                    {{ $homepage['tagline'] ?: 'Your trusted partner for savings, credit, and member-owned financial growth.' }}
                </p>
                @if($homepage['facebook_url'] || $homepage['twitter_url'] || $homepage['instagram_url'] || $homepage['linkedin_url'])
                <div class="fin-social mt-3">
                    @if($homepage['facebook_url'])<a href="{{ $homepage['facebook_url'] }}" target="_blank" rel="noopener" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>@endif
                    @if($homepage['twitter_url'])<a href="{{ $homepage['twitter_url'] }}" target="_blank" rel="noopener" aria-label="Twitter"><i class="fab fa-twitter"></i></a>@endif
                    @if($homepage['instagram_url'])<a href="{{ $homepage['instagram_url'] }}" target="_blank" rel="noopener" aria-label="Instagram"><i class="fab fa-instagram"></i></a>@endif
                    @if($homepage['linkedin_url'])<a href="{{ $homepage['linkedin_url'] }}" target="_blank" rel="noopener" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>@endif
                </div>
                @endif
            </div>

            {{-- Quick links --}}
            <div class="col-lg-2 col-sm-4 mb-4">
                <h6>Products</h6>
                <ul class="fin-footer-links">
                    <li><a href="#products">Savings Accounts</a></li>
                    <li><a href="#products">Member Loans</a></li>
                    <li><a href="#products">Fixed Deposits</a></li>
                    <li><a href="#products">Shares &amp; Dividends</a></li>
                </ul>
            </div>

            {{-- Services --}}
            <div class="col-lg-2 col-sm-4 mb-4">
                <h6>Services</h6>
                <ul class="fin-footer-links">
                    <li><a href="#channels">Internet Banking</a></li>
                    <li><a href="#channels">M-Pesa PayBill</a></li>
                    <li><a href="#channels">USSD Banking</a></li>
                    <li><a href="{{ url('login') }}">Member Portal</a></li>
                </ul>
            </div>

            {{-- Contact --}}
            <div class="col-lg-4 col-sm-4 mb-4">
                <h6>Contact</h6>
                @if(!empty($company_address))
                <p style="font-size:.83rem;opacity:.8;margin-bottom:.5rem;">
                    <i class="fas fa-map-marker-alt mr-2" style="color:var(--fin-accent);"></i>{{ $company_address }}
                </p>
                @endif
                @if(!empty($company_email))
                <p style="font-size:.83rem;opacity:.8;margin-bottom:.5rem;">
                    <i class="fas fa-envelope mr-2" style="color:var(--fin-accent);"></i>
                    <a href="mailto:{{ $company_email }}">{{ $company_email }}</a>
                </p>
                @endif
                @if(!empty($company_phone))
                <p style="font-size:.83rem;opacity:.8;margin-bottom:.5rem;">
                    <i class="fas fa-phone mr-2" style="color:var(--fin-accent);"></i>{{ $company_phone }}
                </p>
                @endif
                <p style="font-size:.83rem;opacity:.8;margin-bottom:0;">
                    <i class="fas fa-clock mr-2" style="color:var(--fin-accent);"></i>{{ $homepage['office_hours'] ?: 'Mon – Fri: 8:00 AM – 5:00 PM' }}
                </p>
            </div>
        </div>

        <div class="fin-footer-divider">
            <span>&copy; {{ date('Y') }} {{ $company_name }}. {{ trans_choice('core::general.all_rights_reserved', 1) }}</span>
            @if(!empty($homepage['license_number']))
            <span style="opacity:.6;font-size:.78rem;">Licensed by SASRA — {{ $homepage['license_number'] }}</span>
            @endif
        </div>
    </div>
</footer>
@endsection

@section('scripts')
<script>
(function () {
    /* ── Sticky nav ── */
    var nav = document.getElementById('finNav');
    if (nav) {
        window.addEventListener('scroll', function () {
            nav.classList.toggle('scrolled', window.scrollY > 20);
        });
    }

    /* ── Hero carousel ── */
    if (typeof $ !== 'undefined') {
        $('#heroCarousel').carousel({ interval: 6000, pause: 'hover' });
    }

    /* ── Animated counters ── */
    var counters = document.querySelectorAll('.stat-counter');
    if (counters.length) {
        var animate = function (el) {
            var target = parseInt(el.getAttribute('data-target'), 10);
            var current = 0;
            var step = Math.max(1, Math.floor(target / 60));
            var timer = setInterval(function () {
                current = Math.min(current + step, target);
                el.textContent = current.toLocaleString();
                if (current >= target) clearInterval(timer);
            }, 20);
        };
        if ('IntersectionObserver' in window) {
            var obs = new IntersectionObserver(function (entries) {
                entries.forEach(function (e) { if (e.isIntersecting) { animate(e.target); obs.unobserve(e.target); } });
            }, { threshold: 0.5 });
            counters.forEach(function (el) { obs.observe(el); });
        } else {
            counters.forEach(animate);
        }
    }

    /* ── Loan calculator ── */
    var amount = document.getElementById('calcAmount');
    var months = document.getElementById('calcMonths');
    var rate   = document.getElementById('calcRate');
    var monthsVal = document.getElementById('calcMonthsVal');

    function calcLoan() {
        var P = parseFloat((amount && amount.value) || 100000);
        var n = parseInt((months && months.value) || 12, 10);
        var r = parseFloat((rate && rate.value) || 12) / 100 / 12;
        if (monthsVal) monthsVal.textContent = n + ' month' + (n !== 1 ? 's' : '');
        if (!P || !n || !r) return;
        var monthly = r === 0 ? P / n : P * r * Math.pow(1 + r, n) / (Math.pow(1 + r, n) - 1);
        var total   = monthly * n;
        var interest = total - P;
        var fmt = function (v) { return 'KES ' + Math.round(v).toLocaleString(); };
        var mEl = document.getElementById('calcMonthly');
        var tEl = document.getElementById('calcTotal');
        var iEl = document.getElementById('calcInterest');
        if (mEl) mEl.textContent = fmt(monthly);
        if (tEl) tEl.textContent = fmt(total);
        if (iEl) iEl.textContent = fmt(interest);
    }

    [amount, months, rate].forEach(function (el) {
        if (el) el.addEventListener('input', calcLoan);
    });
    calcLoan();

    /* ── FAQ accordion ── */
    document.querySelectorAll('.fin-faq-q').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var targetId = this.getAttribute('data-target');
            var panel    = document.getElementById(targetId);
            var isOpen   = this.classList.contains('active');
            /* close all */
            document.querySelectorAll('.fin-faq-q').forEach(function (b) { b.classList.remove('active'); });
            document.querySelectorAll('.fin-faq-a').forEach(function (p) { p.style.display = 'none'; });
            if (!isOpen && panel) {
                this.classList.add('active');
                panel.style.display = 'block';
            }
        });
    });

    /* ── Smooth scroll ── */
    document.querySelectorAll('a[href^="#"]').forEach(function (a) {
        a.addEventListener('click', function (e) {
            var id = this.getAttribute('href').slice(1);
            var el = document.getElementById(id);
            if (el) { e.preventDefault(); el.scrollIntoView({ behavior: 'smooth', block: 'start' }); }
        });
    });
})();
</script>
@endsection
