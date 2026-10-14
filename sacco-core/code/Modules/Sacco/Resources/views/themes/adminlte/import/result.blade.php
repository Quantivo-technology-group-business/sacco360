@extends('core::layouts.master')
@section('title') Import Results @endsection
@section('content')
<section class="content-header">
    <div class="container-fluid">
        <h1>Import Results</h1>
    </div>
</section>
<section class="content">
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header {{ empty($result['errors']) ? 'bg-success' : 'bg-warning' }}">
                    <h3 class="card-title text-white">
                        <i class="fas {{ empty($result['errors']) ? 'fa-check-circle' : 'fa-exclamation-triangle' }} mr-1"></i>
                        Import Complete
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row text-center mb-3">
                        <div class="col-4">
                            <div class="info-box bg-success">
                                <span class="info-box-icon"><i class="fas fa-users"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Imported</span>
                                    <span class="info-box-number">{{ $result['imported'] }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="info-box bg-info">
                                <span class="info-box-icon"><i class="fas fa-list"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Total Rows</span>
                                    <span class="info-box-number">{{ $result['total'] }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="info-box {{ count($result['errors']) > 0 ? 'bg-danger' : 'bg-success' }}">
                                <span class="info-box-icon"><i class="fas fa-times-circle"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Errors</span>
                                    <span class="info-box-number">{{ count($result['errors']) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <p class="text-center text-muted">
                        Success rate: <strong>{{ $result['reconciliation']['success_rate'] }}%</strong>
                    </p>

                    @if(!empty($result['errors']))
                        <hr>
                        <h5><i class="fas fa-exclamation-triangle text-warning mr-1"></i> Row Errors</h5>
                        <div class="table-responsive" style="max-height:300px;overflow-y:auto;">
                            <table class="table table-sm table-bordered">
                                <thead><tr><th>#</th><th>Error</th></tr></thead>
                                <tbody>
                                    @foreach($result['errors'] as $i => $e)
                                        <tr><td>{{ $i + 1 }}</td><td>{{ $e }}</td></tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
                <div class="card-footer">
                    <a href="{{ url('sacco/import') }}" class="btn btn-primary">
                        <i class="fas fa-upload mr-1"></i> Import More
                    </a>
                    <a href="{{ url('client') }}" class="btn btn-default ml-2">View Members</a>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
