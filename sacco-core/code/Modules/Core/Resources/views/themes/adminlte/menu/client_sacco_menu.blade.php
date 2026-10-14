            @if(class_exists(\Modules\Sacco\Services\WingService::class))
            <li class="treeview">
                <a href="#">
                    <i class="fa fa-university"></i> <span>{{ trans('sacco::general.internet_banking') }}</span>
                    <span class="pull-right-container"><i class="fa fa-angle-left pull-right"></i></span>
                </a>
                <ul class="treeview-menu">
                    <li><a href="{{url('portal/sacco/accounts')}}"><i class="fa fa-circle-o"></i> {{ trans('sacco::general.my_accounts') }}</a></li>
                    <li><a href="{{url('portal/sacco/statements')}}"><i class="fa fa-circle-o"></i> {{ trans('sacco::general.account_statements') }}</a></li>
                    <li><a href="{{url('portal/sacco/earmarks')}}"><i class="fa fa-circle-o"></i> {{ trans('sacco::general.earmark', 2) }}</a></li>
                    <li><a href="{{url('portal/sacco/transfer')}}"><i class="fa fa-circle-o"></i> {{ trans_choice('sacco::general.bosa_fosa_transfer', 2) }}</a></li>
                    <li><a href="{{url('portal/sacco/member-transfer/create')}}"><i class="fa fa-circle-o"></i> {{ trans('sacco::general.member_transfer') }}</a></li>
                    <li><a href="{{url('portal/sacco/transfers')}}"><i class="fa fa-circle-o"></i> {{ trans('sacco::general.transfer_history') }}</a></li>
                    <li><a href="{{url('portal/sacco/standing_order')}}"><i class="fa fa-circle-o"></i> {{ trans_choice('sacco::general.standing_order', 2) }}</a></li>
                    <li><a href="{{url('portal/sacco/utility')}}"><i class="fa fa-circle-o"></i> {{ trans('sacco::general.utility_payments') }}</a></li>
                    <li><a href="{{url('portal/sacco/pesalink')}}"><i class="fa fa-circle-o"></i> {{ trans('sacco::general.pesalink') }}</a></li>
                    <li><a href="{{url('portal/sacco/cheques')}}"><i class="fa fa-circle-o"></i> {{ trans('sacco::general.cheque_deposits') }}</a></li>
                    <li><a href="{{url('portal/sacco/beneficiaries')}}"><i class="fa fa-circle-o"></i> {{ trans('sacco::general.beneficiaries') }}</a></li>
                    <li><a href="{{url('portal/sacco/mpesa/send/create')}}"><i class="fa fa-circle-o"></i> {{ trans('sacco::general.send_mpesa') }}</a></li>
                    <li><a href="{{url('portal/sacco/mpesa/withdraw/create')}}"><i class="fa fa-circle-o"></i> {{ trans('sacco::general.withdraw_mpesa') }}</a></li>
                    <li><a href="{{url('portal/sacco/mpesa/transactions')}}"><i class="fa fa-circle-o"></i> {{ trans('sacco::general.mpesa_transactions') }}</a></li>
                    <li><a href="{{url('portal/sacco/fixed_deposit')}}"><i class="fa fa-circle-o"></i> {{ trans('sacco::general.fixed_deposits') }}</a></li>
                    <li><a href="{{url('portal/sacco/dividends')}}"><i class="fa fa-circle-o"></i> {{ trans('sacco::general.dividend_history') }}</a></li>
                    <li><a href="{{url('portal/sacco/checkoff')}}"><i class="fa fa-circle-o"></i> {{ trans('sacco::general.checkoff_deductions') }}</a></li>
                    <li><a href="{{url('portal/sacco/atm')}}"><i class="fa fa-circle-o"></i> {{ trans('sacco::general.atm_history') }}</a></li>
                    <li><a href="{{url('portal/wallet')}}"><i class="fa fa-circle-o"></i> {{ trans('sacco::general.wallet') }}</a></li>
                    <li><a href="{{url('portal/sacco/cards')}}"><i class="fa fa-circle-o"></i> {{ trans_choice('sacco::general.debit_card', 2) }}</a></li>
                    <li><a href="{{url('portal/sacco/deposit')}}"><i class="fa fa-circle-o"></i> {{ trans('sacco::general.deposit_to_fosa') }}</a></li>
                    <li><a href="{{url('portal/sacco/exit')}}"><i class="fa fa-circle-o"></i> {{ trans('sacco::general.exit_settlement') }}</a></li>
                    <li><a href="{{url('portal/sacco/kyc')}}"><i class="fa fa-circle-o"></i> {{ trans('sacco::general.member_kyc') }}</a></li>
                    <li><a href="{{url('portal/share/marketplace')}}"><i class="fa fa-circle-o"></i> {{ trans('sacco::share_marketplace.marketplace') }}</a></li>
                </ul>
            </li>
            @endif