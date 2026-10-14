@extends('core::layouts.master')
@section('title') {{ $group->name }} — Contributions @endsection
@section('styles')
    <link rel="stylesheet" href="{{ asset('assets/plugins/datatables.net-bs/css/dataTables.bootstrap.min.css') }}">
@stop
@section('content')
<section class="content-header">
    <div class="container-fluid">
        <h1>{{ $group->name }} — Contributions</h1>
    </div>
</section>
<section class="content">
    <div class="mb-3">
        <a href="{{ url('sacco/group/'.$group->id.'/show') }}" class="btn btn-default">← Back to Group</a>
        @if($pendingCount > 0 && auth()->user()->can('sacco.groups.approve'))
            <span class="badge badge-warning" style="font-size:1rem;">
                {{ $pendingCount }} awaiting approval
            </span>
        @endif
    </div>

    @if($pendingCount > 0)
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i>
            <strong>{{ $pendingCount }}</strong> member-submitted contribution(s) are awaiting confirmation.
            Members who paid by cash, bank transfer or M-Pesa stay in <em>pending</em> until an officer approves them
            — pending contributions do not count toward group totals or rotation standing.
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Contribution Register</h3>
            <div class="card-tools">
                <form method="GET" class="form-inline">
                    <select name="status" id="filter-status" class="form-control form-control-sm mr-2">
                        <option value="">All statuses</option>
                        <option value="pending">Pending</option>
                        <option value="posted">Posted</option>
                        <option value="rejected">Rejected</option>
                        <option value="reversed">Reversed</option>
                    </select>
                    <input type="month" name="period" id="filter-period" class="form-control form-control-sm">
                </form>
            </div>
        </div>
        <div class="card-body">
            <table id="data-table" class="table table-bordered table-striped">
                <thead>
                <tr>
                    <th>Date</th>
                    <th>Member</th>
                    <th>Period</th>
                    <th>Amount</th>
                    <th>Method</th>
                    <th>Reference</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    {{-- Reject / reverse both need a reason; one shared modal serves both. --}}
    @if(auth()->user()->can('sacco.groups.approve'))
    <div class="modal fade" id="reasonModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <form method="POST" id="reasonForm" class="modal-content">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="reasonTitle">Reason</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="reason">Reason <span class="text-danger">*</span></label>
                        <textarea name="reason" id="reason" class="form-control" rows="3" maxlength="255" required
                                  placeholder="Recorded on the contribution and visible to auditors."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Confirm</button>
                </div>
            </form>
        </div>
    </div>
    @endif
</section>
@endsection
@section('scripts')
    <script src="{{ asset('assets/plugins/datatables.net/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/datatables.net-bs/js/dataTables.bootstrap.min.js') }}"></script>
    <script>
        var baseUrl = '{!! url('sacco/group/'.$group->id) !!}';
        var canApprove = {{ auth()->user()->can('sacco.groups.approve') ? 'true' : 'false' }};

        var table = $('#data-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: baseUrl + '/get_contributions',
                data: function (d) {
                    d.status = $('#filter-status').val();
                    d.period = $('#filter-period').val();
                }
            },
            columns: [
                {data: 'contribution_date', name: 'contribution_date'},
                {data: 'member', name: 'clients.first_name'},
                {data: 'period', name: 'period'},
                {data: 'amount_formatted', name: 'amount'},
                {data: 'method', name: 'payment_method', orderable: false},
                {data: 'reference', name: 'payment_reference', orderable: false},
                {data: 'status_badge', name: 'status'},
                {data: 'action', name: 'action', orderable: false, searchable: false}
            ],
            order: [[0, 'desc']],
            language: {
                lengthMenu: '{{ trans('general.lengthMenu') }}',
                zeroRecords: '{{ trans('general.zeroRecords') }}',
                info: '{{ trans('general.info') }}',
                infoEmpty: '{{ trans('general.infoEmpty') }}',
                search: '{{ trans('general.search') }}',
                infoFiltered: '{{ trans('general.infoFiltered') }}',
                paginate: {
                    first: '{{ trans('general.first') }}',
                    last: '{{ trans('general.last') }}',
                    next: '{{ trans('general.next') }}',
                    previous: '{{ trans('general.previous') }}'
                }
            },
            responsive: false,
            autoWidth: false
        });

        $('#filter-status, #filter-period').on('change', function () {
            table.draw();
        });

        if (canApprove) {
            // Both flows need a reason, so they share one modal and only differ
            // in the endpoint the form posts to.
            function openReasonModal(id, action, title) {
                $('#reasonForm').attr('action', baseUrl + '/contribution/' + id + '/' + action);
                $('#reasonTitle').text(title);
                $('#reason').val('');
                $('#reasonModal').modal('show');
            }

            $(document).on('click', '.reject-btn', function () {
                openReasonModal($(this).data('id'), 'reject', 'Reject contribution');
            });

            $(document).on('click', '.reverse-btn', function () {
                openReasonModal($(this).data('id'), 'reverse', 'Reverse posted contribution');
            });
        }
    </script>
@endsection
