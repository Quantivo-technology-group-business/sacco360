@extends('core::layouts.master')
@section('title') Unseed Import Batch @endsection
@section('content')
<section class="content-header">
    <div class="container-fluid">
        <h1>Unseed Import Batch</h1>
    </div>
</section>
<section class="content">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card card-danger">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-exclamation-triangle mr-1"></i> Confirm Unseed</h3>
                </div>
                <div class="card-body">
                    <p>You are about to <strong>permanently delete</strong> members that were added by this import batch.</p>

                    <table class="table table-bordered table-sm mb-3">
                        <tr><th>File</th><td>{{ $batch->filename }}</td></tr>
                        <tr><th>Imported on</th><td>{{ \Carbon\Carbon::parse($batch->created_at)->format('d M Y H:i') }}</td></tr>
                        <tr><th>Members to delete</th><td><span class="badge badge-danger badge-lg">{{ $count }}</span></td></tr>
                    </table>

                    <div class="callout callout-warning">
                        <p class="mb-0 small">
                            <i class="fas fa-info-circle mr-1"></i>
                            Members who have <strong>active loans</strong> will be <strong>skipped</strong> and not deleted.
                            Their group memberships added during this import will also be removed for deleted members.
                        </p>
                    </div>
                </div>
                <div class="card-footer">
                    <form method="post" action="{{ url('sacco/import/unseed/'.$batch->batch_id) }}">
                        @csrf
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash mr-1"></i> Yes, Delete These {{ $count }} Members
                        </button>
                        <a href="{{ url('sacco/import') }}" class="btn btn-default ml-2">Cancel</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
