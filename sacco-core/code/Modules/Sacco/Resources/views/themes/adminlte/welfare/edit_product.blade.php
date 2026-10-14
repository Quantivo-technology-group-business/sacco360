@extends('core::layouts.master')
@section('title') Edit Welfare Product @endsection
@section('content')
<section class="content-header">
    <div class="container-fluid"><h1>Edit Welfare Product: {{ $product->name }}</h1></div>
</section>
<section class="content">
    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ url('sacco/welfare/products/'.$product->id.'/update') }}">
                @csrf
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Product Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" value="{{ old('name', $product->name) }}" required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Code <span class="text-danger">*</span></label>
                            <input type="text" name="code" class="form-control" value="{{ old('code', $product->code) }}" required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Type <span class="text-danger">*</span></label>
                            <select name="type" class="form-control" required>
                                @foreach(['death','hospitalization','education','disability','maternity','general'] as $t)
                                    <option value="{{ $t }}" {{ old('type', $product->type) === $t ? 'selected' : '' }}>
                                        {{ ucfirst($t) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Monthly Member Contribution <span class="text-danger">*</span></label>
                            <input type="number" name="member_contribution" class="form-control" step="0.01" min="0"
                                   value="{{ old('member_contribution', $product->member_contribution) }}" required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Maximum Claim Amount</label>
                            <input type="number" name="max_claim_amount" class="form-control" step="0.01" min="0"
                                   value="{{ old('max_claim_amount', $product->max_claim_amount) }}"
                                   placeholder="Leave blank for unlimited">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Qualifying Months <span class="text-danger">*</span></label>
                            <input type="number" name="qualifying_months" class="form-control" min="1"
                                   value="{{ old('qualifying_months', $product->qualifying_months) }}" required>
                            <small class="text-muted">Months of contribution before eligible to claim.</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Waiting Period (Days)</label>
                            <input type="number" name="waiting_period_days" class="form-control" min="0"
                                   value="{{ old('waiting_period_days', $product->waiting_period_days) }}">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="description" class="form-control" rows="3">{{ old('description', $product->description) }}</textarea>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-check">
                            <input type="checkbox" name="requires_documents" value="1" class="form-check-input"
                                   id="req_docs" {{ old('requires_documents', $product->requires_documents) ? 'checked' : '' }}>
                            <label class="form-check-label" for="req_docs">Require supporting documents on claim</label>
                        </div>
                        <div class="form-check mt-2">
                            <input type="checkbox" name="is_active" value="1" class="form-check-input"
                                   id="is_active" {{ old('is_active', $product->is_active) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">Active</label>
                        </div>
                    </div>
                </div>
                <div class="mt-3">
                    <button type="submit" class="btn btn-primary">Update Product</button>
                    <a href="{{ url('sacco/welfare/products') }}" class="btn btn-default">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</section>
@endsection
