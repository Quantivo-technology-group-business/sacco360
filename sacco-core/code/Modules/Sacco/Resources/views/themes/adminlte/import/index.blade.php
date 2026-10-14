@extends('core::layouts.master')
@section('title') {{ trans('sacco::general.bulk_import') }} @endsection
@section('content')
<section class="content-header">
    <div class="container-fluid">
        <h1>{{ trans('sacco::general.bulk_import') }}</h1>
    </div>
</section>
<section class="content">
    <div class="row">
        <div class="col-md-8">
            <form method="post" action="{{ url('sacco/import/store') }}" enctype="multipart/form-data">
                @csrf
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Import Members</h3>
                    </div>
                    <div class="card-body">
                        @if($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                            </div>
                        @endif

                        <div class="form-group">
                            <label>Import Type</label>
                            <select name="import_type" class="form-control">
                                <option value="members">Members</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>File <span class="text-muted small">(CSV or Excel)</span></label>
                            <div class="custom-file">
                                <input type="file" name="file" class="custom-file-input" id="importFile" required
                                       accept=".csv,.txt,.xlsx,.xls">
                                <label class="custom-file-label" for="importFile">Choose file…</label>
                            </div>
                            <small class="form-text text-muted mt-1">Accepted formats: .csv, .xlsx, .xls</small>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-upload mr-1"></i> Import Members
                        </button>
                        <a href="{{ url('sacco/import') }}" class="btn btn-default ml-2">Reset</a>
                    </div>
                </div>
            </form>

            {{-- Import History --}}
            @if(isset($batches) && $batches->count())
            <div class="card card-secondary collapsed-card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-history mr-1"></i> Import History (last 20)</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Date</th>
                                <th>File</th>
                                <th>New</th>
                                <th>Updated</th>
                                <th>Errors</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($batches as $b)
                            <tr>
                                <td class="small">{{ \Carbon\Carbon::parse($b->created_at)->format('d M Y H:i') }}</td>
                                <td class="small text-truncate" style="max-width:160px" title="{{ $b->filename }}">{{ $b->filename }}</td>
                                <td><span class="badge badge-success">{{ $b->imported }}</span></td>
                                <td><span class="badge badge-info">{{ $b->updated }}</span></td>
                                <td><span class="badge badge-{{ $b->errors > 0 ? 'danger' : 'secondary' }}">{{ $b->errors }}</span></td>
                                <td>
                                    @if($b->imported > 0)
                                    <a href="{{ url('sacco/import/unseed/'.$b->batch_id.'/confirm') }}"
                                       class="btn btn-xs btn-outline-danger" title="Remove members added in this import">
                                        <i class="fas fa-undo mr-1"></i>Unseed
                                    </a>
                                    @else
                                    <span class="text-muted small">—</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif
        </div>

        <div class="col-md-4">
            <div class="card card-info">
                <div class="card-header"><h3 class="card-title"><i class="fas fa-info-circle mr-1"></i> Instructions</h3></div>
                <div class="card-body">
                    <ol class="pl-3 small mb-0">
                        <li class="mb-2">
                            Download the template for your member type:
                            <div class="mt-1">
                                <strong class="d-block mb-1">Individual Members</strong>
                                <a href="{{ url('sacco/import/template/individual?format=xlsx') }}"
                                   class="btn btn-xs btn-success mr-1">
                                    <i class="fas fa-file-excel mr-1"></i> Excel
                                </a>
                                <a href="{{ url('sacco/import/template/individual?format=csv') }}"
                                   class="btn btn-xs btn-outline-secondary">
                                    <i class="fas fa-download mr-1"></i> CSV
                                </a>
                            </div>
                            <div class="mt-2">
                                <strong class="d-block mb-1">Organisation Members</strong>
                                <a href="{{ url('sacco/import/template/organisation?format=xlsx') }}"
                                   class="btn btn-xs btn-success mr-1">
                                    <i class="fas fa-file-excel mr-1"></i> Excel
                                </a>
                                <a href="{{ url('sacco/import/template/organisation?format=csv') }}"
                                   class="btn btn-xs btn-outline-secondary">
                                    <i class="fas fa-download mr-1"></i> CSV
                                </a>
                            </div>
                        </li>
                        <li class="mb-1">Fill in member details — one member per row.</li>
                        <li class="mb-1"><strong>first_name</strong> is required for every row.</li>
                        <li class="mb-1">Set <strong>client_type</strong> to <code>individual</code> or <code>organisation</code>.</li>
                        <li class="mb-1">Use <strong>bmu_name</strong> to auto-enroll the member into a Chama Group by name.</li>
                        <li class="mb-1">If a member with the same <strong>mobile</strong> or <strong>external_id</strong> already exists, their record will be updated — not duplicated.</li>
                        <li class="mb-1">Upload the completed file and click <strong>Import Members</strong>.</li>
                        <li>To undo an import, use the <strong>Unseed</strong> button in Import History. Members with active loans are protected from deletion.</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
</section>
@push('scripts')
<script>
document.getElementById('importFile').addEventListener('change', function () {
    var fileName = this.files[0] ? this.files[0].name : 'Choose file…';
    this.nextElementSibling.textContent = fileName;
});
</script>
@endpush
@endsection
