<footer class="main-footer">
    <div class="float-right d-none d-sm-block">
        <b>Version</b> {{$system_version}}
    </div>
    <strong>Copyright &copy; {{ date("Y") }} <a href="{{url('/')}}">{{$company_name}}</a>.</strong>
    @if(!empty(trim($footer_text ?? '')))
        <span class="ml-2">{{ trim($footer_text) }}</span>
    @else
        <span class="ml-2">All rights reserved.</span>
    @endif
</footer>