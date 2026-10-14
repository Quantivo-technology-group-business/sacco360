@extends('core::layouts.master')
@section('title') Add Journal Entry @endsection
@section('content')
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Add Journal Entry
                    <a href="#" onclick="window.history.back()" class="btn btn-outline-light bg-white d-none d-sm-inline-flex"><span>Back</span></a>
                </h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{url('dashboard')}}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{url('accounting/journal_entry')}}">Journal Entries</a></li>
                    <li class="breadcrumb-item active">Add</li>
                </ol>
            </div>
        </div>
    </div>
</section>
<section class="content" id="app">
    @if($errors->any())
        <div class="alert alert-danger">
            @foreach($errors->all() as $e) <div>{{ $e }}</div> @endforeach
        </div>
    @endif
    <form method="post" action="{{url('accounting/journal_entry/store')}}">
        {{csrf_field()}}
        <div class="card card-bordered">
            <div class="card-body">
                <div class="row gy-4">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Branch <span class="text-danger">*</span></label>
                            <select class="form-control" name="branch_id" required>
                                <option value="" disabled selected>Select branch</option>
                                @foreach($branches as $b)
                                    <option value="{{$b->id}}" @if(old('branch_id')==$b->id) selected @endif>{{$b->name}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Currency <span class="text-danger">*</span></label>
                            <select class="form-control" name="currency_id" required>
                                <option value="" disabled selected>Select currency</option>
                                @foreach($currencies as $c)
                                    <option value="{{$c->id}}" @if(old('currency_id')==$c->id) selected @endif>{{$c->name}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Date <span class="text-danger">*</span></label>
                            <flat-pickr v-model="date" class="form-control" name="date" required></flat-pickr>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Payment Type</label>
                            <select class="form-control" name="payment_type_id">
                                <option value="">Select</option>
                                @foreach($payment_types as $pt)
                                    <option value="{{$pt->id}}">{{$pt->name}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Reference</label>
                            <input type="text" name="reference" class="form-control" value="{{old('reference')}}">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Bank</label>
                            <input type="text" name="bank_name" class="form-control" value="{{old('bank_name')}}">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Account #</label>
                            <input type="text" name="account_number" class="form-control" value="{{old('account_number')}}">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Cheque / Receipt #</label>
                            <input type="text" name="receipt" class="form-control" value="{{old('receipt')}}">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Notes</label>
                            <input type="text" name="notes" class="form-control" value="{{old('notes')}}">
                        </div>
                    </div>
                </div>

                <hr>
                <h6><strong>Journal Lines</strong>
                    <span class="text-muted small ml-2">(min 2 lines — debits must equal credits)</span>
                    <button type="button" class="btn btn-sm btn-success float-right" @click="addLine">+ Add Line</button>
                </h6>
                @if($errors->has('lines'))
                    <div class="alert alert-danger">{{$errors->first('lines')}}</div>
                @endif
                <table class="table table-bordered table-sm">
                    <thead>
                        <tr style="background:#e9ecef">
                            <th width="40%">Account <span class="text-danger">*</span></th>
                            <th width="18%">Debit</th>
                            <th width="18%">Credit</th>
                            <th width="19%">Description</th>
                            <th width="5%"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(line, idx) in lines" :key="idx">
                            <td>
                                <select :name="'lines['+idx+'][account_id]'" class="form-control form-control-sm" required>
                                    <option value="">— Select Account —</option>
                                    @foreach($chart_of_accounts as $acc)
                                        <option value="{{$acc->id}}">[{{$acc->gl_code}}] {{$acc->name}} ({{ucfirst($acc->account_type)}})</option>
                                    @endforeach
                                </select>
                            </td>
                            <td><input type="number" step="0.0001" min="0" :name="'lines['+idx+'][debit]'" class="form-control form-control-sm" v-model="line.debit" @input="recalc" placeholder="0.00"></td>
                            <td><input type="number" step="0.0001" min="0" :name="'lines['+idx+'][credit]'" class="form-control form-control-sm" v-model="line.credit" @input="recalc" placeholder="0.00"></td>
                            <td><input type="text" :name="'lines['+idx+'][description]'" class="form-control form-control-sm" v-model="line.description" placeholder="Optional"></td>
                            <td><button type="button" class="btn btn-danger btn-xs" @click="removeLine(idx)" :disabled="lines.length<=2">×</button></td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr style="background:#f8f9fa;font-weight:bold">
                            <td class="text-right pr-2">Totals</td>
                            <td class="text-success">@{{ totalDebit.toFixed(2) }}</td>
                            <td class="text-success">@{{ totalCredit.toFixed(2) }}</td>
                            <td colspan="2">
                                <span :class="balanced ? 'badge badge-success' : 'badge badge-danger'">
                                    @{{ balanced ? '✓ Balanced' : '✗ Difference: ' + Math.abs(totalDebit - totalCredit).toFixed(2) }}
                                </span>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary float-right" :disabled="!balanced">
                    Save Journal Entry
                </button>
            </div>
        </div>
    </form>
</section>
@endsection
@section('scripts')
<script>
var app = new Vue({
    el: '#app',
    data: {
        date: "{{old('date', date('Y-m-d'))}}",
        lines: [
            {account_id:'',debit:'',credit:'',description:''},
            {account_id:'',debit:'',credit:'',description:''},
        ],
        totalDebit:  0,
        totalCredit: 0,
    },
    computed: {
        balanced() { return Math.abs(this.totalDebit - this.totalCredit) < 0.001 && this.totalDebit > 0; }
    },
    methods: {
        addLine()     { this.lines.push({account_id:'',debit:'',credit:'',description:''}); },
        removeLine(i) { if(this.lines.length>2) { this.lines.splice(i,1); this.recalc(); } },
        recalc()      {
            this.totalDebit  = this.lines.reduce((s,l)=>s+parseFloat(l.debit ||0),0);
            this.totalCredit = this.lines.reduce((s,l)=>s+parseFloat(l.credit||0),0);
        },
    },
});
</script>
@endsection
