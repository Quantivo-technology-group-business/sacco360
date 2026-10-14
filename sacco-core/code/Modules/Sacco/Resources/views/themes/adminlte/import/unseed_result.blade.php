@extends('core::layouts.master')
@section('title') Unseed Result @endsection
@section('content')
<section class="content-header">
    <div class="container-fluid">
        <h1>Unseed Complete</h1>
    </div>
</section>
<section class="content">
    <div class="row justify-content-center">
        <div class="col-md-6">
            @if(!empty($result['error']))
            <div class="alert alert-danger">{{ $result['error'] }}</div>
            @else
            <div class="card card-{{ $result['skipped'] > 0 ? 'warning' : 'success' }}">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-{{ $result['skipped'] > 0 ? 'exclamation-triangle' : 'check-circle' }} mr-1"></i>
                        Unseed {{ $result['skipped'] > 0 ? 'Partial' : 'Complete' }}
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6">
                            <div class="info-box bg-danger">
                                <span class="info-box-icon"><i class="fas fa-trash"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Deleted</span>
                                    <span class="info-box-number">{{ $result['deleted'] }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="info-box bg-warning">
                                <span class="info-box-icon"><i class="fas fa-shield-alt"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Skipped (active loans)</span>
                                    <span class="info-box-number">{{ $result['skipped'] }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <p class="mt-2">{{ $result['message'] }}</p>
                </div>
                <div class="card-footer">
                    <a href="{{ url('sacco/import') }}" class="btn btn-default">
                        <i class="fas fa-arrow-left mr-1"></i> Back to Import
                    </a>
                    <a href="{{ url('client') }}" class="btn btn-primary ml-2">
                        <i class="fas fa-users mr-1"></i> View Members
                    </a>
                </div>
            </div>
            @endif
        </div>
    </div>
</section>
@endsection
