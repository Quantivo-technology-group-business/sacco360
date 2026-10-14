@extends('core::layouts.master')
@section('title') Dormant Members @endsection
@section('content')
<section class="content-header"><div class="container-fluid"><h1>Dormant Members</h1></div></section>
<section class="content">
    <a href="{{ url('sacco/dormancy') }}" class="btn btn-default mb-3">← Back to Dormancy</a>
    <div class="card"><div class="card-body">
        <table class="table table-bordered table-striped">
            <thead><tr><th>Member No.</th><th>Name</th><th>Email</th><th>Phone</th><th>Last Flagged</th><th></th></tr></thead>
            <tbody>
            @forelse($members as $m)
                <tr>
                    <td>{{ $m->member_number ?? $m->id }}</td>
                    <td>{{ $m->full_name }}</td>
                    <td>{{ $m->email ?? '—' }}</td>
                    <td>{{ $m->mobile ?? '—' }}</td>
                    <td>{{ $m->latestDormancyLog->action_date ?? '—' }}</td>
                    <td>
                        @can('sacco.dormancy.manage')
                        <form method="POST" action="{{ url('sacco/dormancy/'.$m->id.'/reactivate') }}" class="d-inline">
                            @csrf
                            <input type="hidden" name="notes" value="Manual reactivation by staff">
                            <button class="btn btn-sm btn-success" onclick="return confirm('Reactivate this member?')">Reactivate</button>
                        </form>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-success">No dormant members.</td></tr>
            @endforelse
            </tbody>
        </table>
        {{ $members->links() }}
    </div></div>
</section>
@endsection
