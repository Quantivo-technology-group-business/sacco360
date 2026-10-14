<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title> @yield('title') </title>
    <!-- Tell the browser to be responsive to screen width -->
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Source+Sans+Pro:wght@300;400;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" crossorigin="anonymous">
    <link rel="stylesheet" href="{{ asset('themes/adminlte/css/adminlte.css') }}">
    <link rel="stylesheet" href="{{ asset('themes/adminlte/css/custom.css') }}"/>
    @include('core::partials.brand_styles')
    @yield('styles')
    <script src="{{ asset('themes/adminlte/js/adminlte.js') }}"></script>
</head>
<body class="@yield('body_class', 'hold-transition login-page')">
@yield('content')
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
