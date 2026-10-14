@extends('core::layouts.master')
@section('title')
    {{ trans_choice('sacco::general.homepage_settings', 1) }}
@endsection
@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <h1>{{ trans_choice('sacco::general.homepage_settings', 1) }}</h1>
            <p class="text-muted mb-0">{{ trans_choice('sacco::general.homepage_settings_desc', 1) }}</p>
        </div>
    </section>
    <section class="content">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        <form method="post" action="{{ url('sacco/detail/update') }}">
            @csrf
            <div class="card">
                <div class="card-header">{{ trans_choice('sacco::general.homepage_hero', 1) }}</div>
                <div class="card-body">
                    <div class="form-group">
                        <label>{{ trans_choice('sacco::general.tagline', 1) }}</label>
                        <input type="text" name="tagline" class="form-control" value="{{ old('tagline', $detail->tagline) }}">
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{{ trans_choice('sacco::general.primary_color', 1) }}</label>
                                <input type="color" name="primary_color" class="form-control" value="{{ old('primary_color', $detail->primary_color ?: '#0d5c3d') }}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{{ trans_choice('sacco::general.accent_color', 1) }}</label>
                                <input type="color" name="accent_color" class="form-control" value="{{ old('accent_color', $detail->accent_color ?: '#d4a012') }}">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">{{ trans_choice('sacco::general.about_section', 1) }}</div>
                <div class="card-body">
                    <div class="form-group">
                        <label>{{ trans_choice('sacco::general.mission', 1) }}</label>
                        <textarea name="mission" class="form-control" rows="2">{{ old('mission', $detail->mission) }}</textarea>
                    </div>
                    <div class="form-group">
                        <label>{{ trans_choice('sacco::general.vision', 1) }}</label>
                        <textarea name="vision" class="form-control" rows="2">{{ old('vision', $detail->vision) }}</textarea>
                    </div>
                    <div class="form-group">
                        <label>{{ trans_choice('sacco::general.about_text', 1) }}</label>
                        <textarea name="about_text" class="form-control" rows="4">{{ old('about_text', $detail->about_text) }}</textarea>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">{{ trans_choice('sacco::general.regulatory_contact', 1) }}</div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>{{ trans_choice('sacco::general.license_number', 1) }}</label>
                                <input type="text" name="license_number" class="form-control" value="{{ old('license_number', $detail->license_number) }}">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>{{ trans_choice('sacco::general.registration_number', 1) }}</label>
                                <input type="text" name="registration_number" class="form-control" value="{{ old('registration_number', $detail->registration_number) }}">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>{{ trans_choice('sacco::general.founded_year', 1) }}</label>
                                <input type="number" name="founded_year" class="form-control" value="{{ old('founded_year', $detail->founded_year) }}" min="1900" max="{{ date('Y') }}">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>{{ trans_choice('sacco::general.paybill_number', 1) }}</label>
                                <input type="text" name="paybill_number" class="form-control" value="{{ old('paybill_number', $detail->paybill_number) }}">
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="form-group">
                                <label>{{ trans_choice('sacco::general.office_hours', 1) }}</label>
                                <input type="text" name="office_hours" class="form-control" value="{{ old('office_hours', $detail->office_hours) }}">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">{{ trans_choice('sacco::general.homepage_stats', 1) }}</div>
                <div class="card-body">
                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="show_live_stats" name="show_live_stats" value="1" {{ old('show_live_stats', $detail->show_live_stats) ? 'checked' : '' }}>
                            <label class="custom-control-label" for="show_live_stats">{{ trans_choice('sacco::general.show_live_stats', 1) }}</label>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{{ trans_choice('sacco::general.member_count_override', 1) }}</label>
                                <input type="number" name="member_count_override" class="form-control" value="{{ old('member_count_override', $detail->member_count_override) }}" min="0">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{{ trans_choice('sacco::general.branch_count_override', 1) }}</label>
                                <input type="number" name="branch_count_override" class="form-control" value="{{ old('branch_count_override', $detail->branch_count_override) }}" min="0">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">{{ trans_choice('sacco::general.featured_services', 1) }}</div>
                <div class="card-body">
                    @php $services = old('service_title') ? [] : ($detail->featured_services ?: $defaultServices); @endphp
                    @if(old('service_title'))
                        @php
                            $services = [];
                            foreach (old('service_title', []) as $i => $t) {
                                if (trim($t) === '') continue;
                                $services[] = ['icon' => old('service_icon.'.$i), 'title' => $t, 'description' => old('service_description.'.$i)];
                            }
                        @endphp
                    @endif
                    <div id="services-list">
                        @foreach($services as $index => $service)
                            <div class="service-row border rounded p-3 mb-3">
                                <div class="row">
                                    <div class="col-md-2">
                                        <div class="form-group mb-md-0">
                                            <label>{{ trans_choice('sacco::general.service_icon', 1) }}</label>
                                            <input type="text" name="service_icon[]" class="form-control" value="{{ $service['icon'] ?? 'fas fa-star' }}" placeholder="fas fa-piggy-bank">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group mb-md-0">
                                            <label>{{ trans_choice('sacco::general.service_title', 1) }}</label>
                                            <input type="text" name="service_title[]" class="form-control" value="{{ $service['title'] ?? '' }}" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group mb-md-0">
                                            <label>{{ trans_choice('sacco::general.service_description', 1) }}</label>
                                            <input type="text" name="service_description[]" class="form-control" value="{{ $service['description'] ?? '' }}">
                                        </div>
                                    </div>
                                    <div class="col-md-1 d-flex align-items-end">
                                        <button type="button" class="btn btn-outline-danger btn-sm remove-service mb-2">&times;</button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="add-service">{{ trans_choice('sacco::general.add_service', 1) }}</button>
                </div>
            </div>

            <div class="card">
                <div class="card-header">{{ trans_choice('sacco::general.social_links', 1) }}</div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Facebook</label>
                                <input type="url" name="facebook_url" class="form-control" value="{{ old('facebook_url', $detail->facebook_url) }}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Twitter / X</label>
                                <input type="url" name="twitter_url" class="form-control" value="{{ old('twitter_url', $detail->twitter_url) }}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Instagram</label>
                                <input type="url" name="instagram_url" class="form-control" value="{{ old('instagram_url', $detail->instagram_url) }}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>LinkedIn</label>
                                <input type="url" name="linkedin_url" class="form-control" value="{{ old('linkedin_url', $detail->linkedin_url) }}">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mb-4">
                <button type="submit" class="btn btn-primary">{{ trans_choice('core::general.save', 1) }}</button>
                <a href="{{ url('/') }}" target="_blank" class="btn btn-outline-secondary ml-2">{{ trans_choice('sacco::general.preview_homepage', 1) }}</a>
            </div>
        </form>
    </section>
@endsection
@section('scripts')
    <script>
        document.getElementById('add-service').addEventListener('click', function () {
            var row = document.createElement('div');
            row.className = 'service-row border rounded p-3 mb-3';
            row.innerHTML = '<div class="row">' +
                '<div class="col-md-2"><div class="form-group mb-md-0"><label>{{ trans_choice('sacco::general.service_icon', 1) }}</label>' +
                '<input type="text" name="service_icon[]" class="form-control" value="fas fa-star"></div></div>' +
                '<div class="col-md-3"><div class="form-group mb-md-0"><label>{{ trans_choice('sacco::general.service_title', 1) }}</label>' +
                '<input type="text" name="service_title[]" class="form-control" required></div></div>' +
                '<div class="col-md-6"><div class="form-group mb-md-0"><label>{{ trans_choice('sacco::general.service_description', 1) }}</label>' +
                '<input type="text" name="service_description[]" class="form-control"></div></div>' +
                '<div class="col-md-1 d-flex align-items-end"><button type="button" class="btn btn-outline-danger btn-sm remove-service mb-2">&times;</button></div>' +
                '</div>';
            document.getElementById('services-list').appendChild(row);
        });
        document.addEventListener('click', function (e) {
            if (e.target.classList.contains('remove-service')) {
                var rows = document.querySelectorAll('.service-row');
                if (rows.length > 1) {
                    e.target.closest('.service-row').remove();
                }
            }
        });
    </script>
@endsection
