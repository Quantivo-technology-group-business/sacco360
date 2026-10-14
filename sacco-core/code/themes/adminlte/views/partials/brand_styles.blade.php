@php
    $brandPrimary = $brand_primary ?? '#0d5c3d';
    $brandAccent = $brand_accent ?? '#d4a012';
@endphp
<style>
    :root {
        --sacco-primary: {{ $brandPrimary }};
        --sacco-accent: {{ $brandAccent }};
        --sacco-primary-dark: color-mix(in srgb, {{ $brandPrimary }} 80%, #000);
        --primary: {{ $brandPrimary }};
        --blue: {{ $brandPrimary }};
    }
</style>
<link rel="stylesheet" href="{{ asset('themes/adminlte/css/admin-brand.css') }}"/>
