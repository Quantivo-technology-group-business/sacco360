<div class="grid-stack-item compliance_prudential"
     gs-x="{{$config['x'] ?? 0}}" gs-y="{{$config['y'] ?? 0}}"
     gs-w="{{$config['width'] ?? 12}}" gs-h="{{$config['height'] ?? 2}}" gs-id="CompliancePrudential">
    <div class="grid-stack-item-content">
        <div class="card h-100 mb-0">
            <div class="card-header">
                <h3 class="card-title">SASRA Prudential Ratios</h3>
                <div class="card-tools"><a href="{{url('sacco/compliance')}}" class="btn btn-sm btn-default">Details</a></div>
            </div>
            <div class="card-body p-2">
                <div class="row">
                    @foreach(['core_capital_to_assets','core_capital_to_deposits','liquidity_ratio'] as $key)
                    @php $r = $ratios[$key] ?? null; @endphp
                    @if($r)
                    <div class="col-4 text-center">
                        <small>{{$r['label']}}</small>
                        <div><strong>{{number_format($r['value'],1)}}%</strong></div>
                        <span class="badge badge-{{$r['status'] === 'green' ? 'success' : ($r['status'] === 'amber' ? 'warning' : 'danger')}}">{{$r['status']}}</span>
                    </div>
                    @endif
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
