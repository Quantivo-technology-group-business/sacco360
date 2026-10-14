@php
    $template = !empty($templateKey) ? config('core.upload_templates.' . $templateKey) : null;
@endphp
@if($template)
    <div class="upload-template-hint mt-1 mb-2">
        @if(!empty($template['headers']))
            <p class="mb-1">
                <a href="{{ url('upload-template/' . $templateKey . '?format=csv') }}" class="btn btn-xs btn-outline-secondary mr-2">
                    <i class="fas fa-download mr-1"></i> Download CSV Template
                </a>
                <a href="{{ url('upload-template/' . $templateKey . '?format=xlsx') }}" class="btn btn-xs btn-outline-success">
                    <i class="fas fa-file-excel mr-1"></i> Download Excel Template
                </a>
            </p>
        @endif
        @if(!empty($template['columns_hint']))
            <p class="text-muted small mb-1">{{ trans('core::general.required_columns') }}: {{ $template['columns_hint'] }}</p>
        @endif
        @if(!empty($template['accept']))
            <p class="text-muted small mb-1">{{ trans('core::general.accepted_formats') }}: {{ $template['accept'] }}</p>
        @endif
        @if(!empty($template['notes']))
            <p class="text-muted small mb-0">{{ $template['notes'] }}</p>
        @endif
    </div>
@endif
