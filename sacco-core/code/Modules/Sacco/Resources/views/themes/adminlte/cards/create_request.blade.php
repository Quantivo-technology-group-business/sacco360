@extends('core::layouts.master')
@section('title') New Card Request @endsection
@section('content')
<section class="content-header"><div class="container-fluid"><h1>New Card Request</h1></div></section>
<section class="content">
  <div class="row">
    {{-- Left Column: Member Selection & Details --}}
    <div class="col-md-5">
      <div class="card card-primary card-outline">
        <div class="card-header"><h3 class="card-title"><i class="fas fa-user"></i> Member Selection</h3></div>
        <div class="card-body">
          <div class="form-group">
            <label>Select Member *</label>
            <select id="client_select" class="form-control select2" style="width:100%">
              <option value="">— Search & Select Member —</option>
              @foreach($clients as $c)
                <option value="{{ $c->id }}" {{ (old('client_id', $client->id ?? '') == $c->id) ? 'selected' : '' }}>
                  {{ $c->first_name }} {{ $c->middle_name }} {{ $c->last_name }} ({{ $c->member_number ?? $c->external_id ?? $c->id }})
                </option>
              @endforeach
            </select>
          </div>
        </div>
      </div>

      {{-- Member Details Panel (shown after selection) --}}
      <div id="member_details_panel" class="card card-info card-outline" style="display:none">
        <div class="card-header">
          <h3 class="card-title"><i class="fas fa-id-card"></i> Member Details</h3>
          <div class="card-tools">
            <span id="member_status_badge" class="badge badge-success">Active</span>
          </div>
        </div>
        <div class="card-body p-0">
          <div class="row no-gutters">
            {{-- Photo --}}
            <div class="col-md-4 text-center p-3 border-right">
              <div id="member_photo_container">
                <img id="member_photo" src="" alt="Member Photo" class="img-fluid img-thumbnail" style="max-height:140px;display:none">
                <div id="member_photo_placeholder" class="bg-secondary text-white d-flex align-items-center justify-content-center" style="width:120px;height:140px;margin:0 auto;border-radius:4px">
                  <i class="fas fa-user fa-4x"></i>
                </div>
              </div>
              <h5 id="member_name" class="mt-2 mb-0 font-weight-bold"></h5>
              <small id="member_number" class="text-muted"></small>
            </div>
            {{-- Basic Info --}}
            <div class="col-md-8 p-3">
              <table class="table table-sm table-borderless mb-0">
                <tr><td class="text-muted" width="35%">Gender:</td><td id="member_gender" class="font-weight-bold"></td></tr>
                <tr><td class="text-muted">Date of Birth:</td><td><span id="member_dob"></span> <small id="member_age" class="text-muted"></small></td></tr>
                <tr><td class="text-muted">Marital Status:</td><td id="member_marital"></td></tr>
                <tr><td class="text-muted">Mobile:</td><td id="member_mobile" class="font-weight-bold"></td></tr>
                <tr><td class="text-muted">Email:</td><td id="member_email"></td></tr>
                <tr><td class="text-muted">Branch:</td><td id="member_branch"></td></tr>
              </table>
            </div>
          </div>
          {{-- Additional Info --}}
          <div class="p-3 border-top bg-light">
            <div class="row">
              <div class="col-md-6">
                <small class="text-muted">Address</small>
                <p id="member_address" class="mb-2"></p>
              </div>
              <div class="col-md-6">
                <small class="text-muted">Employer / Profession</small>
                <p class="mb-2"><span id="member_employer"></span> <small class="text-muted">(<span id="member_profession"></span>)</small></p>
              </div>
            </div>
            <div class="row">
              <div class="col-md-6">
                <small class="text-muted">ID Documents</small>
                <div id="member_ids" class="mb-2"></div>
              </div>
              <div class="col-md-6">
                <small class="text-muted">Member Since</small>
                <p id="member_since" class="mb-0"></p>
              </div>
            </div>
          </div>
          {{-- Existing Cards Warning --}}
          <div id="existing_cards_section" class="p-3 border-top bg-warning-light" style="display:none">
            <h6 class="text-warning"><i class="fas fa-exclamation-triangle"></i> Existing Cards</h6>
            <div id="existing_cards_list"></div>
          </div>
        </div>
      </div>
    </div>

    {{-- Right Column: Card Request Form --}}
    <div class="col-md-7">
      <div id="card_request_form_panel" class="card card-success card-outline" style="display:none">
        <div class="card-header"><h3 class="card-title"><i class="fas fa-credit-card"></i> Card Request Details</h3></div>
        <form method="POST" action="{{ url('sacco/cards/requests/store') }}" id="card_request_form">
          @csrf
          <input type="hidden" name="client_id" id="form_client_id" value="">
          <div class="card-body">
            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label>FOSA Account *</label>
                  <select name="savings_id" id="savings_select" class="form-control" required>
                    <option value="">— Select Account —</option>
                  </select>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label>Request Type *</label>
                  <select name="request_type" class="form-control" id="req_type" required>
                    <option value="new">New Card</option>
                    <option value="replacement">Replacement</option>
                    <option value="virtual">Virtual Card</option>
                  </select>
                </div>
              </div>
            </div>

            {{-- Replacement Options --}}
            <div id="replacement_row" class="row" style="display:none">
              <div class="col-md-6">
                <div class="form-group">
                  <label>Replacement Reason</label>
                  <select name="replacement_reason" class="form-control">
                    <option value="">— N/A —</option>
                    @foreach(['lost','stolen','damaged','expired','name_change','other'] as $rr)
                      <option value="{{ $rr }}">{{ ucfirst($rr) }}</option>
                    @endforeach
                  </select>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label>Card Being Replaced</label>
                  <select name="replaced_card_id" id="replaced_card_select" class="form-control">
                    <option value="">— Select —</option>
                  </select>
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label>Name to Emboss on Card</label>
                  <input type="text" name="preferred_name" id="preferred_name" class="form-control text-uppercase" maxlength="26" placeholder="Name as it will appear on card">
                  <small class="text-muted">Max 26 characters</small>
                </div>
              </div>
              <div class="col-md-3">
                <div class="form-group">
                  <label>Card Network *</label>
                  <select name="card_network" class="form-control">
                    <option value="visa">Visa</option>
                    <option value="mastercard">Mastercard</option>
                    <option value="local">Local</option>
                  </select>
                </div>
              </div>
              <div class="col-md-3">
                <div class="form-group">
                  <label>Card Type *</label>
                  <select name="card_type" class="form-control">
                    <option value="physical">Physical</option>
                    <option value="virtual">Virtual</option>
                  </select>
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-md-3">
                <div class="form-group">
                  <label>Card Fee</label>
                  <input type="number" name="card_fee" class="form-control" step="0.01" min="0" value="0">
                </div>
              </div>
              <div class="col-md-3">
                <div class="form-group">
                  <label>&nbsp;</label>
                  <div class="form-check mt-2">
                    <input type="checkbox" name="fee_waived" value="1" class="form-check-input" id="fw">
                    <label for="fw" class="form-check-label">Waive Fee</label>
                  </div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label>Delivery Method</label>
                  <select name="delivery_method" class="form-control" id="delivery_method">
                    <option value="branch_pickup">Branch Pickup</option>
                    <option value="courier">Courier</option>
                    <option value="post">Post</option>
                  </select>
                </div>
              </div>
            </div>

            <div id="delivery_address_row" class="row" style="display:none">
              <div class="col-md-12">
                <div class="form-group">
                  <label>Delivery Address</label>
                  <input type="text" name="delivery_address" id="delivery_address" class="form-control" placeholder="Enter delivery address">
                </div>
              </div>
            </div>

            <div class="form-group">
              <label>Notes</label>
              <textarea name="notes" class="form-control" rows="2" placeholder="Additional notes for this request"></textarea>
            </div>
          </div>
          <div class="card-footer">
            <button type="submit" class="btn btn-success"><i class="fas fa-paper-plane"></i> Submit Request</button>
            <a href="{{ url('sacco/cards/requests') }}" class="btn btn-default">Cancel</a>
          </div>
        </form>
      </div>

      {{-- Placeholder when no member selected --}}
      <div id="no_member_placeholder" class="card">
        <div class="card-body text-center text-muted py-5">
          <i class="fas fa-hand-point-left fa-3x mb-3"></i>
          <h5>Select a Member First</h5>
          <p>Choose a member from the dropdown to view their details and create a card request.</p>
        </div>
      </div>
    </div>
  </div>
</section>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    const clientSelect = $('#client_select');
    const memberPanel = $('#member_details_panel');
    const formPanel = $('#card_request_form_panel');
    const noMemberPlaceholder = $('#no_member_placeholder');

    // Initialize select2
    clientSelect.select2({
        placeholder: "Search by name or member number...",
        allowClear: true
    });

    // Handle member selection change
    clientSelect.on('change', function() {
        const clientId = $(this).val();
        if (!clientId) {
            memberPanel.hide();
            formPanel.hide();
            noMemberPlaceholder.show();
            return;
        }
        
        // Fetch member details via AJAX
        $.ajax({
            url: "{{ url('sacco/cards/client') }}/" + clientId + "/details",
            type: 'GET',
            dataType: 'json',
            beforeSend: function() {
                memberPanel.find('.card-body').css('opacity', '0.5');
            },
            success: function(data) {
                populateMemberDetails(data);
                memberPanel.show().find('.card-body').css('opacity', '1');
                formPanel.show();
                noMemberPlaceholder.hide();
            },
            error: function() {
                alert('Error fetching member details. Please try again.');
                memberPanel.find('.card-body').css('opacity', '1');
            }
        });
    });

    function populateMemberDetails(data) {
        // Set form hidden field
        $('#form_client_id').val(data.id);
        
        // Basic info
        $('#member_name').text(data.full_name);
        $('#member_number').text('Member #' + data.member_number);
        $('#member_gender').text(data.gender);
        $('#member_dob').text(data.dob);
        $('#member_age').text(data.age !== 'N/A' ? '(' + data.age + ')' : '');
        $('#member_marital').text(data.marital_status);
        $('#member_mobile').text(data.mobile);
        $('#member_email').text(data.email);
        $('#member_branch').text(data.branch);
        $('#member_employer').text(data.employer);
        $('#member_profession').text(data.profession);
        $('#member_since').text(data.created_date);
        
        // Address
        let address = data.address;
        if (data.city !== 'N/A') address += ', ' + data.city;
        if (data.state !== 'N/A') address += ', ' + data.state;
        if (data.zip !== 'N/A') address += ' ' + data.zip;
        $('#member_address').text(address);
        
        // Prefill delivery address
        $('#delivery_address').val(address !== 'N/A' ? address : '');
        
        // Status badge
        const statusBadge = $('#member_status_badge');
        statusBadge.text(data.status);
        statusBadge.removeClass('badge-success badge-warning badge-danger badge-secondary');
        if (data.status === 'Active') statusBadge.addClass('badge-success');
        else if (data.status === 'Pending') statusBadge.addClass('badge-warning');
        else if (data.status === 'Inactive' || data.status === 'Closed') statusBadge.addClass('badge-danger');
        else statusBadge.addClass('badge-secondary');
        
        // Photo
        if (data.photo) {
            $('#member_photo').attr('src', data.photo).show();
            $('#member_photo_placeholder').hide();
        } else {
            $('#member_photo').hide();
            $('#member_photo_placeholder').show();
        }
        
        // ID documents
        let idsHtml = '';
        if (data.identifications && data.identifications.length > 0) {
            data.identifications.forEach(function(id) {
                idsHtml += '<span class="badge badge-light mr-1">' + id.type + ': ' + id.number + '</span>';
            });
        } else {
            idsHtml = '<span class="text-muted">No IDs on file</span>';
        }
        $('#member_ids').html(idsHtml);
        
        // Savings accounts dropdown
        const savingsSelect = $('#savings_select');
        savingsSelect.empty().append('<option value="">— Select Account —</option>');
        if (data.savings && data.savings.length > 0) {
            data.savings.forEach(function(s) {
                if (s.status === 'active') {
                    savingsSelect.append('<option value="' + s.id + '">' + s.account_number + ' (Bal: ' + s.balance + ')</option>');
                }
            });
        }
        
        // Preferred name for card embossing
        $('#preferred_name').val(data.full_name.toUpperCase().substring(0, 26));
        
        // Existing cards
        const existingCardsSection = $('#existing_cards_section');
        const existingCardsList = $('#existing_cards_list');
        const replacedCardSelect = $('#replaced_card_select');
        replacedCardSelect.empty().append('<option value="">— Select —</option>');
        
        if (data.existing_cards && data.existing_cards.length > 0) {
            let cardsHtml = '<table class="table table-sm table-borderless mb-0">';
            data.existing_cards.forEach(function(card) {
                let statusClass = card.status === 'Active' ? 'success' : (card.status === 'Blocked' ? 'danger' : 'warning');
                cardsHtml += '<tr>';
                cardsHtml += '<td><i class="fas fa-credit-card"></i> ' + card.masked + '</td>';
                cardsHtml += '<td>' + card.type + '</td>';
                cardsHtml += '<td><span class="badge badge-' + statusClass + '">' + card.status + '</span></td>';
                cardsHtml += '<td>Exp: ' + card.expiry + '</td>';
                cardsHtml += '</tr>';
                
                // Add to replacement dropdown
                replacedCardSelect.append('<option value="' + card.id + '">' + card.masked + ' (' + card.status + ')</option>');
            });
            cardsHtml += '</table>';
            existingCardsList.html(cardsHtml);
            existingCardsSection.show();
        } else {
            existingCardsSection.hide();
        }
    }

    // Request type change handler
    $('#req_type').on('change', function() {
        $('#replacement_row').toggle(this.value === 'replacement');
    });

    // Delivery method change handler
    $('#delivery_method').on('change', function() {
        $('#delivery_address_row').toggle(this.value !== 'branch_pickup');
    });

    // Auto-load if client_id is pre-selected (from URL parameter)
    @if($client)
        clientSelect.trigger('change');
    @endif
});
</script>
@endpush

@push('styles')
<style>
.bg-warning-light { background-color: #fff3cd; }
#member_details_panel .table td { padding: 0.25rem 0.5rem; }
#member_details_panel .card-body { transition: opacity 0.3s; }
</style>
@endpush
@endsection
