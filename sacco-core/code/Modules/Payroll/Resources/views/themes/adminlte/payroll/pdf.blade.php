
<style>
    .borderOk {

        border-right: solid 1px #000000;
        border-left: solid 1px #000000;
        border-top: solid 1px #000000;
        border-bottom: solid 1px #000000;
    }

    table #hours_and_earnings td, table #tax_deductions td, table #pre_tax_deductions td, table #after_tax_deductions td, table #payslip_employee_header td, table #payslip_employer_header td, table #pay_period_and_salary td, table #summary td, table #net_pay_distribution td, table #messages td {
        padding: 2px;
    }

    .bg-navy {
        background-color: #001f3f;
        color: #fff;
    }

    .bg-gray {
        color: #000;
        background-color: #d2d6de;
    }

    .text-bold, .text-bold.table td, .text-bold.table th {
        font-weight: 700;
    }

    .margin {
        margin: 10px;
    }

    .text-center {
        text-align: center;
    }
</style>
<h3 class="text-center"><b>{{\App\Models\Setting::where('setting_key','core.company_name')->first()->setting_value}}</b></h3>

<h3 class="text-center"><b>{{trans_choice('payroll::general.payslip',1)}}</b></h3>
<table width="100%">
    <tbody>
    <tr style="margin: 20px">
        <td style="padding-bottom:10px;">
            <table width="100%" class="borderOk">
                <tbody>
                <tr>
                    <td style="vertical-align: top;" width="50%">

                        <table width="100%" id="payslip_employee_header">
                            <tbody>
                            <tr>
                                <td width="50%" class="cell_format">
                                    <div class="margin"> {{trans_choice('payroll::general.employee',1)}} {{trans_choice('core::general.name',1)}}
                                    </div>
                                </td>
                                <td width="50%" class="cell_format">
                                    <div class="margin text-bold">
                                      {{$payroll->employee_name}}
                                      @if(!empty($payroll->employee_number)) (#{{$payroll->employee_number}}) @endif
                                    </div>
                                </td>
                            </tr>
                            @if(!empty($payroll->department))
                            <tr><td class="cell_format"><div class="margin">{{trans_choice('payroll::general.department',1)}}</div></td><td class="cell_format"><div class="margin text-bold">{{$payroll->department}}</div></td></tr>
                            @endif
                            @if(!empty($payroll->kra_pin))
                            <tr><td class="cell_format"><div class="margin">KRA PIN</div></td><td class="cell_format"><div class="margin text-bold">{{$payroll->kra_pin}}</div></td></tr>
                            @endif

                            </tbody>
                        </table>
                    </td>

                    <td style="vertical-align: top" width="50%">

                        <table width="100%" id="pay_period_and_salary">

                            <tbody>
                            <tr>
                                <td width="50%" class="cell_format">
                                    <div class="margin"><b>{{trans_choice('payroll::general.payroll',1)}} {{trans_choice('core::general.date',1)}}</b></div>
                                </td>
                                <td width="50%" class="cell_format">
                                    <div class="margin text-bold">
                                       {{$payroll->date}}
                                    </div>
                                </td>
                            </tr>

                            </tbody>
                        </table>
                        <!--Pay Period and Salary-->
                    </td>
                </tr>
                </tbody>
            </table>
        </td>
    </tr>
    <tr style="height: 20px">
        <td></td>
    </tr>
    <tr>
        <td>
            <table width="100%" class="borderOk">
                <tbody>
                <tr>
                    <td style="vertical-align: top" width="50%" class="borderRight">

                        <table width="100%" id="hours_and_earnings">
                            <tbody>
                            <tr>
                                <td width="50%" class="bg-navy"><b>{{trans_choice('core::general.description',1)}}</b></td>
                                <td width="50%" class="bg-navy"><b>{{trans_choice('core::general.amount',1)}}</b></td>
                            </tr>

                            <tr>
                                <td width="50%" class="cell_format">
                                    <div class="margin">

                                            {{trans_choice('payroll::general.basic_salary',1)}}

                                    </div>
                                </td>
                                <td width="50%" class="cell_format">
                                    <div class="margin text-bold">
                                        {{number_format($payroll->basic_salary ?? $payroll->total_duration_amount,2)}}
                                    </div>
                                </td>
                            </tr>
                            @foreach($payroll->payroll_items->where('type','allowance')->all() as $key)
                                <tr>
                                    <td width="50%" class="cell_format">
                                        <div class="margin">

                                            {{$key->name}}
                                        </div>
                                    </td>
                                    <td width="50%" class="cell_format">
                                        <div class="margin text-bold">
                                            {{number_format($key->computed_amount ?? $key->amount,2)}}
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                        <!--Hours and Earnings-->
                    </td>

                    <td width="50%" valign="top">
                        <table width="100%" id="pre_tax_deductions">
                            <tbody>
                            <tr>
                                <td width="50%" class="bg-navy"><b>{{trans_choice('core::general.description',1)}}</b></td>
                                <td width="50%" class="bg-navy"><b>{{trans_choice('core::general.amount',1)}}</b></td>
                            </tr>
                            @foreach($payroll->payroll_items->where('type','deduction')->all() as $key)
                                <tr>
                                    <td width="50%" class="cell_format">
                                        <div class="margin">

                                            {{$key->name}}
                                        </div>
                                    </td>
                                    <td width="50%" class="cell_format">
                                        <div class="margin text-bold">
                                            {{number_format($key->computed_amount ?? $key->amount,2)}}
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                        <!--Pre-Tax Deductions-->
                    </td>
                </tr>
                <tr>
                    <td width="50%" class="bg-gray">
                        <table width="100%" id="gross_pay">
                            <tbody>
                            <tr>
                                <td width="50%" class="cell_format">
                                    <div class="margin"><b>{{trans_choice('payroll::general.total',1)}} {{trans_choice('payroll::general.allowance',2)}}</b></div>
                                </td>
                                <td width="50%" class="cell_format">
                                    <div class="margin text-bold">
                                       {{number_format($payroll->total_allowances+$payroll->total_duration_amount,2)}}
                                    </div>
                                </td>
                            </tr>
                            </tbody>
                        </table>

                    </td>
                    <td width="50%" class="bg-gray">

                        <table width="100%" id="gross_pay">
                            <tbody>
                            <tr>
                                <td width="50%" class="cell_format">
                                    <div class="margin"><b>{{trans_choice('payroll::general.total',1)}} {{trans_choice('payroll::general.deduction',2)}}</b></div>
                                </td>
                                <td width="50%" class="cell_format">
                                    <div class="margin text-bold">
                                        {{number_format($payroll->total_deductions,2)}}
                                    </div>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td width="50%">
                        <br>
                    </td>
                    <td width="50%" class="bg-gray">
                        <table width="100%" id="gross_pay">
                            <tbody>
                            <tr>
                                <td width="50%" class="cell_format">
                                    <div class="margin"><b>{{trans_choice('payroll::general.net',1)}} {{trans_choice('payroll::general.pay',1)}}</b></div>
                                </td>
                                <td width="50%" class="cell_format">
                                    <div class="margin text-bold">
                                        {{number_format($payroll->net_pay ?? $payroll->gross_amount,2)}}
                                    </div>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </td>
                </tr>
                @if(($payroll->paye ?? 0) > 0)
                <tr>
                    <td colspan="2" style="padding:10px;">
                        <table width="100%" class="borderOk">
                            <tr class="bg-navy"><td colspan="2"><b>Statutory Summary</b></td></tr>
                            <tr><td>PAYE</td><td class="text-bold">{{number_format($payroll->paye,2)}}</td></tr>
                            <tr><td>NSSF (Employee)</td><td class="text-bold">{{number_format($payroll->nssf_employee,2)}}</td></tr>
                            <tr><td>SHIF</td><td class="text-bold">{{number_format($payroll->shif,2)}}</td></tr>
                            <tr><td>Housing Levy</td><td class="text-bold">{{number_format($payroll->housing_levy_employee,2)}}</td></tr>
                            <tr><td>Employer NSSF</td><td class="text-bold">{{number_format($payroll->nssf_employer,2)}}</td></tr>
                            <tr><td>Employer Housing Levy</td><td class="text-bold">{{number_format($payroll->housing_levy_employer,2)}}</td></tr>
                        </table>
                    </td>
                </tr>
                @endif
                @if(($payroll->ytd_gross ?? 0) > 0)
                <tr>
                    <td colspan="2" style="padding:10px;">
                        <table width="100%" class="borderOk">
                            <tr class="bg-navy"><td colspan="2"><b>{{trans_choice('payroll::general.ytd',1)}}</b></td></tr>
                            <tr><td>YTD Gross</td><td>{{number_format($payroll->ytd_gross,2)}}</td></tr>
                            <tr><td>YTD PAYE</td><td>{{number_format($payroll->ytd_paye,2)}}</td></tr>
                            <tr><td>YTD Net</td><td>{{number_format($payroll->ytd_net,2)}}</td></tr>
                        </table>
                    </td>
                </tr>
                @endif
                </tbody>
            </table>
        </td>
    </tr>
    <tr style="height: 20px">
        <td></td>
    </tr>

    <tr>
        <td style="padding-top:10px;">
            <table  width="100%" class="borderOk" id="net_pay_distribution">
                <thead>
                <tr>
                    <td colspan="5" class="bg-navy">
                        <b>{{trans_choice('general.net_pay_distribution',1)}}</b>
                    </td>
                </tr>
                <tr>
                    <th width="20%" class="cell_format">{{trans_choice('core::general.bank',1)}}</th>
                    <th width="20%" class="cell_format">{{trans_choice('core::general.account',1)}}</th>
                    <th width="20%" class="cell_format">{{trans_choice('core::general.payment',1)}} {{trans_choice('payroll::general.type',1)}}</th>
                    <th width="20%" class="cell_format">{{trans_choice('payroll::general.amount',1)}}</th>
                    <th width="20%" class="cell_format">{{trans_choice('core::general.date',1)}}</th>
                </tr>
                </thead>
                <tbody>
                @foreach($payroll->payroll_payments as $key)
                    <tr >
                        <td width="20%" class="cell_format">
                            <div class="margin text-bold">
                            @if(!empty($key->payment_detail))
                                    {{$key->payment_detail->bank_name}}
                            @endif
                            </div>
                        </td>
                        <td width="20%" class="cell_format">
                            <div class="margin text-bold">
                            @if(!empty($key->payment_detail))
                                {{$key->payment_detail->account_number}}
                            @endif
                            </div>
                        </td>
                        <td width="20%" class="cell_format">
                            <div class="margin text-bold">
                            @if(!empty($key->payment_detail))
                                @if(!empty($key->payment_detail->payment_type))
                                    {{$key->payment_detail->payment_type->name}}
                                @endif
                            @endif
                            </div>
                        </td>
                        <td width="20%" class="cell_format">
                            <div class="margin text-bold">{{number_format($key->amount,2)}}
                            </div>
                        </td>
                        <td width="20%" class="cell_format">
                            <div class="margin text-bold">
                            {{$key->submitted_on}}
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </td>
    </tr>
    @if(!empty($payroll->description))
        <tr style="height: 20px">
            <td></td>
        </tr>
        <tr>
            <td>
                <table width="100%" class="borderOk" style="margin-top:10px;padding: 10px" id="messages">
                    <tbody>
                    <tr>
                        <td width="100%" class="cell_format">
                            <div class="margin"><b>{{trans_choice('core::general.description',2)}}</b></div>
                        </td>
                    </tr>
                    <tr>
                        <td width="100%" class="cell_format">
                            <div class="margin text-bold">
                                {!! $payroll->description !!}
                            </div>
                        </td>
                    </tr>
                    </tbody>
                </table>
                <!--Messages-->
            </td>
        </tr>
    @endif
    </tbody>
</table>