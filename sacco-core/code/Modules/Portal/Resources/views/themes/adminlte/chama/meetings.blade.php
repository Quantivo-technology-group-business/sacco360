@extends('core::layouts.master')
@section('title') Meetings & AGM @endsection
@section('content')

<section class="content-header">
    <div class="container-fluid">
        <h1><i class="fas fa-calendar-check mr-2"></i>Meetings &amp; AGM</h1>
    </div>
</section>

<section class="content">
    <div class="container-fluid">

        <div class="card">
            <div class="card-body p-0">
                <table class="table table-bordered table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Type</th>
                            <th>Date</th>
                            <th>Venue</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($meetings as $m)
                        <tr>
                            <td>{{ $m->title }}</td>
                            <td>{{ strtoupper(str_replace('_', ' ', $m->meeting_type)) }}</td>
                            <td>{{ \Carbon\Carbon::parse($m->scheduled_date)->format('d M Y') }}</td>
                            <td>{{ $m->venue ?? '—' }}</td>
                            <td>
                                @php
                                    $sc = ['scheduled' => 'primary', 'held' => 'success', 'postponed' => 'warning', 'cancelled' => 'danger'];
                                @endphp
                                <span class="badge badge-{{ $sc[$m->status] ?? 'secondary' }}">
                                    {{ ucfirst($m->status) }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ url('portal/sacco/chama/meetings/' . $m->id . '/show') }}"
                                   class="btn btn-sm btn-info">
                                    <i class="fas fa-eye"></i> View
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">No meetings scheduled.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            @if($meetings->hasPages())
            <div class="card-footer">
                {{ $meetings->links() }}
            </div>
            @endif
        </div>

        <a href="{{ url('portal/sacco/chama') }}" class="btn btn-default">
            <i class="fas fa-arrow-left mr-1"></i> Back to My Groups
        </a>

    </div>
</section>

@endsection
