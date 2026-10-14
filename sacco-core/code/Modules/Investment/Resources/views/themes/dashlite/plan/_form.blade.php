@php $editing = !is_null($plan); @endphp
<div class="row">
<div class="col-md-8">
<div class="card">
  <div class="card-header"><h5 class="mb-0">Plan Details</h5></div>
  <div class="card-body">
    <div class="row">
      <div class="col-md-6">
        <div class="form-group">
          <label>Plan Name <span class="text-danger">*</span></label>
          <input type="text" name="name" class="form-control" value="{{old('name',$plan->name??'')}}" required>
        </div>
      </div>
      <div class="col-md-3">
        <div class="form-group">
          <label>Plan Code <span class="text-danger">*</span></label>
          <input type="text" name="code" class="form-control" value="{{old('code',$plan->code??'')}}" required @if($editing) readonly @endif>
        </div>
      </div>
      <div class="col-md-3">
        <div class="form-group">
          <label>Plan Type <span class="text-danger">*</span></label>
          <select name="plan_type" class="form-control" required>
            @foreach(['fixed_deposit'=>'Fixed Deposit','bond'=>'Bond','unit_trust'=>'Unit Trust','equity'=>'Equity','real_estate'=>'Real Estate','money_market'=>'Money Market','other'=>'Other'] as $v=>$l)
              <option value="{{$v}}" @if(old('plan_type',$plan->plan_type??'fixed_deposit')==$v) selected @endif>{{$l}}</option>
            @endforeach
          </select>
        </div>
      </div>
      <div class="col-md-12">
        <div class="form-group">
          <label>Description</label>
          <textarea name="description" class="form-control" rows="2">{{old('description',$plan->description??'')}}</textarea>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-header"><h5 class="mb-0">Returns & Interest</h5></div>
  <div class="card-body">
    <div class="row">
      <div class="col-md-4">
        <div class="form-group">
          <label>Rate Type</label>
          <select name="return_rate_type" class="form-control" v-model="rateType">
            <option value="fixed">Fixed Rate</option>
            <option value="variable">Variable Rate</option>
            <option value="tiered">Tiered Rate</option>
          </select>
        </div>
      </div>
      <div class="col-md-4" v-if="rateType !== 'tiered'">
        <div class="form-group">
          <label>Annual Return Rate (%) <span class="text-danger">*</span></label>
          <input type="number" step="0.0001" min="0" name="return_rate" class="form-control" value="{{old('return_rate',$plan->return_rate??'')}}">
        </div>
      </div>
      <div class="col-md-4">
        <div class="form-group">
          <label>Interest Method</label>
          <select name="interest_method" class="form-control">
            <option value="compound" @if(old('interest_method',$plan->interest_method??'compound')=='compound') selected @endif>Compound</option>
            <option value="simple"   @if(old('interest_method',$plan->interest_method??'')=='simple')   selected @endif>Simple</option>
          </select>
        </div>
      </div>
      <div class="col-md-4">
        <div class="form-group">
          <label>Compounding Frequency</label>
          <select name="compounding_frequency" class="form-control">
            @foreach(['daily'=>'Daily','monthly'=>'Monthly','quarterly'=>'Quarterly','annually'=>'Annually'] as $v=>$l)
              <option value="{{$v}}" @if(old('compounding_frequency',$plan->compounding_frequency??'monthly')==$v) selected @endif>{{$l}}</option>
            @endforeach
          </select>
        </div>
      </div>
      <div class="col-md-4">
        <div class="form-group">
          <label>Profit Posting Frequency</label>
          <select name="profit_posting_frequency" class="form-control">
            @foreach(['monthly'=>'Monthly','quarterly'=>'Quarterly','semi_annual'=>'Semi-Annual','annual'=>'Annual','on_maturity'=>'On Maturity'] as $v=>$l)
              <option value="{{$v}}" @if(old('profit_posting_frequency',$plan->profit_posting_frequency??'monthly')==$v) selected @endif>{{$l}}</option>
            @endforeach
          </select>
        </div>
      </div>
    </div>

    {{-- Tiered rate rows --}}
    <div v-if="rateType==='tiered'" class="mt-2">
      <h6><strong>Rate Tiers</strong> <button type="button" class="btn btn-xs btn-success ml-2" @click="addTier">+ Add Tier</button></h6>
      <table class="table table-sm table-bordered">
        <thead><tr><th>Label</th><th>Min Amount</th><th>Max Amount</th><th>Rate %</th><th></th></tr></thead>
        <tbody>
          <tr v-for="(t,i) in tiers" :key="i">
            <td><input type="text" :name="'tiers['+i+'][label]'" class="form-control form-control-sm" v-model="t.label" placeholder="e.g. Gold"></td>
            <td><input type="number" :name="'tiers['+i+'][min_amount]'" class="form-control form-control-sm" v-model="t.min_amount" min="0"></td>
            <td><input type="number" :name="'tiers['+i+'][max_amount]'" class="form-control form-control-sm" v-model="t.max_amount" placeholder="Leave blank = unlimited"></td>
            <td><input type="number" step="0.0001" :name="'tiers['+i+'][return_rate]'" class="form-control form-control-sm" v-model="t.return_rate" min="0"></td>
            <td><button type="button" class="btn btn-danger btn-xs" @click="tiers.splice(i,1)">×</button></td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-header"><h5 class="mb-0">Amount & Duration</h5></div>
  <div class="card-body">
    <div class="row">
      <div class="col-md-3">
        <div class="form-group">
          <label>Minimum Amount <span class="text-danger">*</span></label>
          <input type="number" step="0.01" min="0" name="min_amount" class="form-control" value="{{old('min_amount',$plan->min_amount??0)}}" required>
        </div>
      </div>
      <div class="col-md-3">
        <div class="form-group">
          <label>Maximum Amount <small class="text-muted">(blank = unlimited)</small></label>
          <input type="number" step="0.01" min="0" name="max_amount" class="form-control" value="{{old('max_amount',$plan->max_amount??'')}}">
        </div>
      </div>
      <div class="col-md-3">
        <div class="form-group">
          <label>Min Duration (months) <span class="text-danger">*</span></label>
          <input type="number" min="1" name="min_duration_months" class="form-control" value="{{old('min_duration_months',$plan->min_duration_months??1)}}" required>
        </div>
      </div>
      <div class="col-md-3">
        <div class="form-group">
          <label>Max Duration (months) <small class="text-muted">(blank = open)</small></label>
          <input type="number" min="1" name="max_duration_months" class="form-control" value="{{old('max_duration_months',$plan->max_duration_months??'')}}">
        </div>
      </div>
      <div class="col-md-3">
        <div class="form-group">
          <label>Unit Price <small class="text-muted">(for unit-based plans)</small></label>
          <input type="number" step="0.0001" min="0" name="unit_price" class="form-control" value="{{old('unit_price',$plan->unit_price??'')}}">
        </div>
      </div>
      <div class="col-md-3">
        <div class="form-group">
          <label>Total Units Available <small class="text-muted">(blank = unlimited)</small></label>
          <input type="number" min="0" name="total_units_available" class="form-control" value="{{old('total_units_available',$plan->total_units_available??'')}}">
        </div>
      </div>
      <div class="col-md-3">
        <div class="form-group">
          <label>Allow Early Withdrawal</label>
          <select name="allow_early_withdrawal" class="form-control">
            <option value="0" @if(!($plan->allow_early_withdrawal??0)) selected @endif>No</option>
            <option value="1" @if(($plan->allow_early_withdrawal??0)==1) selected @endif>Yes</option>
          </select>
        </div>
      </div>
      <div class="col-md-3">
        <div class="form-group">
          <label>Early Withdrawal Penalty (%)</label>
          <input type="number" step="0.01" min="0" name="early_withdrawal_penalty_rate" class="form-control" value="{{old('early_withdrawal_penalty_rate',$plan->early_withdrawal_penalty_rate??0)}}">
        </div>
      </div>
    </div>
  </div>
</div>
</div>

{{-- Right column: GL accounts + settings --}}
<div class="col-md-4">
<div class="card card-warning card-outline">
  <div class="card-header"><h5 class="mb-0"><i class="fas fa-book mr-1"></i>GL Account Linkage</h5></div>
  <div class="card-body">
    <p class="text-muted small">Link to Chart of Accounts for automatic journal entries.</p>
    @foreach([
      'investment_control_account_id' => 'Investment Control (Liability) — funds held for members',
      'interest_expense_account_id'   => 'Profit / Interest Expense Account',
      'cash_account_id'               => 'Cash / Bank Account (Asset)',
      'profit_payable_account_id'     => 'Profit Payable Account (Liability)',
    ] as $field => $label)
    <div class="form-group">
      <label class="small">{{$label}}</label>
      <select name="{{$field}}" class="form-control form-control-sm select2">
        <option value="">— Not linked —</option>
        @foreach($accounts as $acc)
          <option value="{{$acc->id}}" @if(old($field,optional($plan)->{$field}??'')==$acc->id) selected @endif>
            [{{$acc->gl_code}}] {{$acc->name}}
          </option>
        @endforeach
      </select>
    </div>
    @endforeach
  </div>
</div>

<div class="card">
  <div class="card-header"><h5 class="mb-0">Settings</h5></div>
  <div class="card-body">
    <div class="form-group">
      <label>Active</label>
      <select name="active" class="form-control">
        <option value="1" @if(old('active',$plan->active??1)==1) selected @endif>Yes</option>
        <option value="0" @if(old('active',$plan->active??1)==0) selected @endif>No</option>
      </select>
    </div>
    <div class="form-group">
      <label>Open to Members (Portal)</label>
      <select name="open_to_members" class="form-control">
        <option value="1" @if(old('open_to_members',$plan->open_to_members??1)==1) selected @endif>Yes</option>
        <option value="0" @if(old('open_to_members',$plan->open_to_members??1)==0) selected @endif>No</option>
      </select>
    </div>
    <div class="form-group">
      <label>Terms & Conditions</label>
      <textarea name="terms_conditions" class="form-control" rows="4">{{old('terms_conditions',$plan->terms_conditions??'')}}</textarea>
    </div>
  </div>
</div>

<button type="submit" class="btn btn-primary btn-block btn-lg">
  <i class="fas fa-save mr-1"></i> {{$editing ? 'Update Plan' : 'Create Plan'}}
</button>
<a href="{{url('investment/plan')}}" class="btn btn-secondary btn-block">Cancel</a>
</div>
</div>
