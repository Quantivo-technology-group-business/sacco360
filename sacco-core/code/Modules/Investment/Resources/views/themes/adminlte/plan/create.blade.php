@extends('core::layouts.master')
@section('title') Create Investment Plan @endsection
@section('content')
<section class="content-header">
    <div class="container-fluid"><div class="row mb-2">
        <div class="col-sm-6"><h1>Create Investment Plan</h1></div>
        <div class="col-sm-6"><ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="{{url('dashboard')}}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{url('investment/plan')}}">Plans</a></li>
            <li class="breadcrumb-item active">Create</li>
        </ol></div>
    </div></div>
</section>
<section class="content" id="planApp">
@if($errors->any())
<div class="alert alert-danger">@foreach($errors->all() as $e)<div>{{$e}}</div>@endforeach</div>
@endif
<form method="post" action="{{url('investment/plan/store')}}">
@csrf
@include('investment::plan._form',['plan'=>null])
</form>
</section>
@endsection
@include('investment::plan._scripts')
