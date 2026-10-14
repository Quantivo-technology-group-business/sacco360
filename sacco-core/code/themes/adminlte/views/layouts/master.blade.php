<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ !empty($app_name) ? $app_name : config('app.name') }} - @yield('title')</title>
    <!-- Tell the browser to be responsive to screen width -->
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
    <link rel="icon" href="{{ !empty($favicon) ? asset('storage/uploads/' . $favicon) : asset('themes/adminlte/img/favicon.ico') }}">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" crossorigin="anonymous">
    <link rel="stylesheet" href="{{ asset('themes/adminlte/css/adminlte.css') }}" type="text/css">
    <link rel="stylesheet" href="{{ asset('themes/adminlte/css/custom.css') }}" type="text/css"/>
    @include('core::partials.brand_styles')
    @yield('styles')
    <script src="{{ asset('themes/adminlte/js/adminlte.js') }}"></script>
</head>
<body class="hold-transition sidebar-mini layout-fixed layout-navbar-fixed">
<div class="wrapper">
    @include('core::partials.top_nav')
    @if(Auth::user()->hasRole('client'))
        @include('core::menu.client')
    @else
        @include('core::menu.admin')
    @endif
    <div class="content-wrapper">
        <section class="content pt-2">
            <div class="row">
                <div class="col-md-12">
                    @include('core::partials.flash.message')
                </div>
            </div>
        </section>
        @yield('content')
    </div>
    @include('core::partials.footer')
</div>
<script>
    $(document).ready(function () {
        $('#flash-overlay-modal').modal();
        if (window.bindDatatableConfirmHandlers) {
            window.bindDatatableConfirmHandlers();
        }
    })
</script>
@yield('scripts')
@include('core::partials.datatables')
<script src="{{ asset('themes/adminlte/js/custom.js') }}"></script>
</body>
</html>
