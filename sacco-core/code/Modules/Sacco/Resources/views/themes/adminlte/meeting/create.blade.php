@extends('core::layouts.master')
@section('title') Schedule Meeting @endsection
@section('content')
<section class="content-header"><div class="container-fluid"><h1>Schedule Meeting</h1></div></section>
<section class="content"><div class="card"><div class="card-body">
    <form method="POST" action="{{ url('sacco/meeting/store') }}">@csrf
        <div class="row">
            <div class="col-md-6"><div class="form-group"><label>Title <span class="text-danger">*</span></label>
                <input type="text" name="title" class="form-control" value="{{ old('title') }}" required></div></div>
            <div class="col-md-3"><div class="form-group"><label>Type <span class="text-danger">*</span></label>
                <select name="meeting_type" class="form-control" required>
                    <option value="agm">AGM (Annual General Meeting)</option>
                    <option value="sgm">SGM (Special General Meeting)</option>
                    <option value="board">Board Meeting</option>
                    <option value="credit_committee">Credit Committee</option>
                    <option value="supervisory">Supervisory Committee</option>
                    <option value="management">Management Meeting</option>
                    <option value="other">Other</option>
                </select></div></div>
            <div class="col-md-3"><div class="form-group"><label>Branch</label>
                <select name="branch_id" class="form-control"><option value="">— All —</option>
                @foreach($branches as $b)<option value="{{ $b->id }}">{{ $b->name }}</option>@endforeach
                </select></div></div>
            <div class="col-md-3"><div class="form-group">
                <label>Chama Group</label>
                <select name="sacco_group_id" class="form-control">
                    <option value="">— SACCO-wide (no group) —</option>
                    @foreach($groups as $g)
                        <option value="{{ $g->id }}" @if(old('sacco_group_id') == $g->id) selected @endif>
                            {{ $g->name }}
                        </option>
                    @endforeach
                </select>
                <small class="form-text text-muted">
                    Group meetings are visible in the member portal only to that group's members.
                    SACCO-wide meetings are visible to all members only when the type is AGM, SGM or Other —
                    board, credit-committee, supervisory and management minutes stay internal.
                </small>
            </div></div>
        </div>
        <div class="row">
            <div class="col-md-2"><div class="form-group"><label>Date <span class="text-danger">*</span></label>
                <input type="date" name="scheduled_date" class="form-control" value="{{ old('scheduled_date') }}" required></div></div>
            <div class="col-md-2"><div class="form-group"><label>Time</label>
                <input type="time" name="scheduled_time" class="form-control" value="{{ old('scheduled_time','09:00') }}"></div></div>
            <div class="col-md-4"><div class="form-group"><label>Venue</label>
                <input type="text" name="venue" class="form-control" value="{{ old('venue') }}"></div></div>
            <div class="col-md-2"><div class="form-group"><label>Quorum Required</label>
                <input type="number" name="quorum_required" class="form-control" min="1" value="{{ old('quorum_required') }}"></div></div>
        </div>
        <div class="form-group"><label>Meeting Notice / Agenda Summary</label>
            <textarea name="notice" class="form-control" rows="3">{{ old('notice') }}</textarea></div>

        <h5 class="mt-3">Agenda Items</h5>
        <div id="agenda-items">
            @for($i=0;$i<3;$i++)
            <div class="row mb-2 agenda-item">
                <div class="col-md-5"><input type="text" name="agenda_titles[]" class="form-control" placeholder="Agenda item {{ $i+1 }}"></div>
                <div class="col-md-4"><input type="text" name="agenda_descriptions[]" class="form-control" placeholder="Description (optional)"></div>
                <div class="col-md-3"><input type="text" name="agenda_presenters[]" class="form-control" placeholder="Presenter (optional)"></div>
            </div>
            @endfor
        </div>
        <button type="button" class="btn btn-sm btn-secondary mb-3" onclick="addAgendaRow()"><i class="fas fa-plus"></i> Add Agenda Item</button>

        <div class="mt-3">
            <button type="submit" class="btn btn-primary">Schedule Meeting</button>
            <a href="{{ url('sacco/meeting') }}" class="btn btn-default">Cancel</a>
        </div>
    </form>
</div></div></section>
@push('scripts')
<script>
function addAgendaRow(){
    var c = document.querySelectorAll('.agenda-item').length+1;
    var row = '<div class="row mb-2 agenda-item"><div class="col-md-5"><input type="text" name="agenda_titles[]" class="form-control" placeholder="Agenda item '+c+'"></div><div class="col-md-4"><input type="text" name="agenda_descriptions[]" class="form-control" placeholder="Description (optional)"></div><div class="col-md-3"><input type="text" name="agenda_presenters[]" class="form-control" placeholder="Presenter (optional)"></div></div>';
    document.getElementById('agenda-items').insertAdjacentHTML('beforeend',row);
}
</script>
@endpush
@endsection
