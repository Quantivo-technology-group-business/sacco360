<aside class="main-sidebar sidebar-dark-primary elevation-4">
    {{-- Brand --}}
    <a href="{{ url('/') }}" class="brand-link">
        @php
            $logoVal = config('app.installed')
                ? optional(\Modules\Setting\Entities\Setting::where('setting_key','core.company_logo')->first())->setting_value
                : null;
            $nameVal = config('app.installed')
                ? optional(\Modules\Setting\Entities\Setting::where('setting_key','core.company_name')->first())->setting_value
                : config('app.name','ULM');
        @endphp
        @if(!empty($logoVal))
            <img class="brand-image img-circle elevation-3"
                 src="{{ asset('storage/uploads/'.$logoVal) }}"
                 alt="{{ $nameVal }}">
        @endif
        <span class="brand-text font-weight-bold">{{ $nameVal }}</span>
    </a>

    <div class="sidebar">
        {{-- User panel --}}
        <div class="user-panel mt-3 pb-3 mb-3 d-flex">
            <div class="image">
                @if(!empty(Auth::user()->photo))
                    <img class="img-circle elevation-2"
                         src="{{ asset('storage/uploads/'.Auth::user()->photo) }}"
                         alt="{{ Auth::user()->first_name }}">
                @else
                    <img class="img-circle elevation-2"
                         src="{{ asset('themes/adminlte/img/user.png') }}"
                         alt="User">
                @endif
            </div>
            <div class="info">
                <a href="{{ url('user/profile') }}" class="d-block">
                    {{ Auth::user()->first_name }} {{ Auth::user()->last_name }}
                </a>
            </div>
        </div>

        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column nav-child-indent"
                data-widget="treeview" role="menu" data-accordion="true">

                {{-- ── Section label ─────────────────────────────────────── --}}
                <li class="nav-header">DEPARTMENTS</li>

                {{-- ══════════════════════════════════════════════════════════
                     1. DASHBOARD
                ══════════════════════════════════════════════════════════ --}}
                <li class="nav-item">
                    <a href="{{ url('dashboard') }}"
                       class="nav-link {{ Request::is('dashboard') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-home"></i>
                        <p>Dashboard</p>
                    </a>
                </li>

                {{-- ══════════════════════════════════════════════════════════
                     2. FINANCE MANAGEMENT
                     Accounting · Income · Expenses · Reports · Assets
                ══════════════════════════════════════════════════════════ --}}
                @php
                    $financeActive = Request::is('accounting*','income*','expense*','report*','asset*');
                @endphp
                <li class="nav-item has-treeview {{ $financeActive ? 'menu-open' : '' }}">
                    <a href="#" class="nav-link {{ $financeActive ? 'active' : '' }}">
                        <i class="nav-icon fas fa-chart-line"></i>
                        <p>Finance Management <i class="right fas fa-angle-left"></i></p>
                    </a>
                    <ul class="nav nav-treeview">
                        @can('accounting.chart_of_accounts.index')
                        <li class="nav-item">
                            <a href="{{ url('accounting/chart_of_account') }}"
                               class="nav-link {{ Request::is('accounting/chart_of_account*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-sitemap"></i>
                                <p>Chart of Accounts</p>
                            </a>
                        </li>
                        @endcan
                        @can('accounting.journal_entries.index')
                        <li class="nav-item">
                            <a href="{{ url('accounting/journal_entry') }}"
                               class="nav-link {{ Request::is('accounting/journal_entry*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-book"></i>
                                <p>Journal Entries</p>
                            </a>
                        </li>
                        @endcan
                        @can('income.income.index')
                        <li class="nav-item">
                            <a href="{{ url('income') }}"
                               class="nav-link {{ Request::is('income*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-arrow-circle-down"></i>
                                <p>Income</p>
                            </a>
                        </li>
                        @endcan
                        @can('expense.expenses.index')
                        <li class="nav-item">
                            <a href="{{ url('expense') }}"
                               class="nav-link {{ Request::is('expense*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-arrow-circle-up"></i>
                                <p>Expenses</p>
                            </a>
                        </li>
                        @endcan
                        @can('reports.index')
                        <li class="nav-item">
                            <a href="{{ url('report') }}"
                               class="nav-link {{ Request::is('report*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-chart-bar"></i>
                                <p>Reports</p>
                            </a>
                        </li>
                        @endcan
                        @can('asset.assets.index')
                        <li class="nav-item">
                            <a href="{{ url('asset') }}"
                               class="nav-link {{ Request::is('asset*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-boxes"></i>
                                <p>Assets</p>
                            </a>
                        </li>
                        @endcan
                    </ul>
                </li>

                {{-- ══════════════════════════════════════════════════════════
                     3. FUND MANAGEMENT
                     Savings · Shares · Dividends · Fixed Deposits · Bank Recon
                ══════════════════════════════════════════════════════════ --}}
                @php
                    $fundActive = Request::is('savings*','share*','sacco/dividend*','sacco/fixed_deposit*','sacco/reconciliation*');
                @endphp
                <li class="nav-item has-treeview {{ $fundActive ? 'menu-open' : '' }}">
                    <a href="#" class="nav-link {{ $fundActive ? 'active' : '' }}">
                        <i class="nav-icon fas fa-piggy-bank"></i>
                        <p>Fund Management <i class="right fas fa-angle-left"></i></p>
                    </a>
                    <ul class="nav nav-treeview">
                        @can('savings.savings.index')
                        <li class="nav-item">
                            <a href="{{ url('savings') }}"
                               class="nav-link {{ Request::is('savings') || Request::is('savings/*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-wallet"></i>
                                <p>Savings Accounts</p>
                            </a>
                        </li>
                        @endcan
                        @can('share.shares.index')
                        <li class="nav-item">
                            <a href="{{ url('share') }}"
                               class="nav-link {{ Request::is('share*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-database"></i>
                                <p>Share Capital</p>
                            </a>
                        </li>
                        @endcan
                        @can('sacco.dividends.index')
                        <li class="nav-item">
                            <a href="{{ url('sacco/dividend') }}"
                               class="nav-link {{ Request::is('sacco/dividend*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-percentage"></i>
                                <p>Dividends</p>
                            </a>
                        </li>
                        @endcan
                        @can('sacco.fixed_deposits.index')
                        <li class="nav-item">
                            <a href="{{ url('sacco/fixed_deposit') }}"
                               class="nav-link {{ Request::is('sacco/fixed_deposit*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-lock"></i>
                                <p>Fixed Deposits</p>
                            </a>
                        </li>
                        @endcan
                        @can('sacco.reconciliation.index')
                        <li class="nav-item">
                            <a href="{{ url('sacco/reconciliation') }}"
                               class="nav-link {{ Request::is('sacco/reconciliation*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-balance-scale"></i>
                                <p>Bank Reconciliation</p>
                            </a>
                        </li>
                        @endcan
                    </ul>
                </li>

                {{-- ══════════════════════════════════════════════════════════
                     4. REGISTRY MANAGEMENT
                     Clients · Member 360 · Branches · KYC
                ══════════════════════════════════════════════════════════ --}}
                @php
                    $registryActive = Request::is('client*','branch*','sacco/kyc*');
                @endphp
                <li class="nav-item has-treeview {{ $registryActive ? 'menu-open' : '' }}">
                    <a href="#" class="nav-link {{ $registryActive ? 'active' : '' }}">
                        <i class="nav-icon fas fa-address-book"></i>
                        <p>Registry Management <i class="right fas fa-angle-left"></i></p>
                    </a>
                    <ul class="nav nav-treeview">
                        @can('client.clients.index')
                        <li class="nav-item">
                            <a href="{{ url('client') }}"
                               class="nav-link {{ Request::is('client') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-users"></i>
                                <p>View Members</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ url('client/member360') }}"
                               class="nav-link {{ Request::is('client/member360*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-user-circle"></i>
                                <p>Member 360</p>
                            </a>
                        </li>
                        @endcan
                        @can('client.clients.create')
                        <li class="nav-item">
                            <a href="{{ url('client/create') }}"
                               class="nav-link {{ Request::is('client/create') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-user-plus"></i>
                                <p>New Member</p>
                            </a>
                        </li>
                        @endcan
                        @can('branch.branches.index')
                        <li class="nav-item">
                            <a href="{{ url('branch') }}"
                               class="nav-link {{ Request::is('branch*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-building"></i>
                                <p>Branches</p>
                            </a>
                        </li>
                        @endcan
                        @can('sacco.kyc.index')
                        <li class="nav-item">
                            <a href="{{ url('sacco/kyc') }}"
                               class="nav-link {{ Request::is('sacco/kyc*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-id-card"></i>
                                <p>Member KYC</p>
                            </a>
                        </li>
                        @endcan
                        @can('sacco.earmarks.index')
                        <li class="nav-item">
                            <a href="{{ url('sacco/earmark') }}"
                               class="nav-link {{ Request::is('sacco/earmark*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-tag"></i>
                                <p>Member Earmarks</p>
                            </a>
                        </li>
                        @endcan
                    </ul>
                </li>

                {{-- ══════════════════════════════════════════════════════════
                     5. CREDIT MANAGEMENT
                     Loans · Applications · Check-Off · Calculator
                ══════════════════════════════════════════════════════════ --}}
                @php
                    $creditActive = Request::is('loan*','sacco/checkoff*');
                @endphp
                <li class="nav-item has-treeview {{ $creditActive ? 'menu-open' : '' }}">
                    <a href="#" class="nav-link {{ $creditActive ? 'active' : '' }}">
                        <i class="nav-icon fas fa-hand-holding-usd"></i>
                        <p>Credit Management <i class="right fas fa-angle-left"></i></p>
                    </a>
                    <ul class="nav nav-treeview">
                        @can('loan.loans.index')
                        <li class="nav-item">
                            <a href="{{ url('loan') }}"
                               class="nav-link {{ Request::is('loan') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-money-bill-wave"></i>
                                <p>View Loans</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ url('loan/application') }}"
                               class="nav-link {{ Request::is('loan/application*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-file-alt"></i>
                                <p>Applications</p>
                            </a>
                        </li>
                        @endcan
                        @can('loan.loans.create')
                        <li class="nav-item">
                            <a href="{{ url('loan/create') }}"
                               class="nav-link {{ Request::is('loan/create') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-plus-circle"></i>
                                <p>New Loan</p>
                            </a>
                        </li>
                        @endcan
                        @can('loan.loans.products.index')
                        <li class="nav-item">
                            <a href="{{ url('loan/product') }}"
                               class="nav-link {{ Request::is('loan/product*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-tags"></i>
                                <p>Loan Products</p>
                            </a>
                        </li>
                        @endcan
                        @can('loan.loans.charges.index')
                        <li class="nav-item">
                            <a href="{{ url('loan/charge') }}"
                               class="nav-link {{ Request::is('loan/charge*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-percent"></i>
                                <p>Loan Charges</p>
                            </a>
                        </li>
                        @endcan
                        @can('sacco.checkoff.index')
                        <li class="nav-item">
                            <a href="{{ url('sacco/checkoff') }}"
                               class="nav-link {{ Request::is('sacco/checkoff*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-receipt"></i>
                                <p>Check-Off</p>
                            </a>
                        </li>
                        @endcan
                        @can('loan.loans.index')
                        <li class="nav-item">
                            <a href="{{ url('loan/calculator') }}"
                               class="nav-link {{ Request::is('loan/calculator*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-calculator"></i>
                                <p>Loan Calculator</p>
                            </a>
                        </li>
                        @endcan
                    </ul>
                </li>

                {{-- ══════════════════════════════════════════════════════════
                     6. BANKING MANAGEMENT
                     Member Search · Transfers · Teller · Standing Orders ·
                     Cheques · ATM · Exit Settlement
                ══════════════════════════════════════════════════════════ --}}
                @php
                    $bankingActive = Request::is(
                        'sacco/search*','sacco/transfer*','sacco/share-transfer*',
                        'sacco/teller*','sacco/standing_order*','sacco/cheque*',
                        'sacco/atm*','sacco/exit*'
                    );
                @endphp
                <li class="nav-item has-treeview {{ $bankingActive ? 'menu-open' : '' }}">
                    <a href="#" class="nav-link {{ $bankingActive ? 'active' : '' }}">
                        <i class="nav-icon fas fa-university"></i>
                        <p>Banking Management <i class="right fas fa-angle-left"></i></p>
                    </a>
                    <ul class="nav nav-treeview">
                        @can('sacco.search.index')
                        <li class="nav-item">
                            <a href="{{ url('sacco/search') }}"
                               class="nav-link {{ Request::is('sacco/search*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-search"></i>
                                <p>Member Search</p>
                            </a>
                        </li>
                        @endcan
                        @can('sacco.transfers.index')
                        <li class="nav-item">
                            <a href="{{ url('sacco/transfer') }}"
                               class="nav-link {{ Request::is('sacco/transfer*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-exchange-alt"></i>
                                <p>BOSA/FOSA Transfers</p>
                            </a>
                        </li>
                        @endcan
                        @can('sacco.share_transfers.index')
                        <li class="nav-item">
                            <a href="{{ url('sacco/share-transfer') }}"
                               class="nav-link {{ Request::is('sacco/share-transfer*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-random"></i>
                                <p>Share Transfers</p>
                            </a>
                        </li>
                        @endcan
                        @can('sacco.teller.index')
                        <li class="nav-item">
                            <a href="{{ url('sacco/teller') }}"
                               class="nav-link {{ Request::is('sacco/teller*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-cash-register"></i>
                                <p>Teller Desk</p>
                            </a>
                        </li>
                        @endcan
                        @can('sacco.standing_orders.index')
                        <li class="nav-item">
                            <a href="{{ url('sacco/standing_order') }}"
                               class="nav-link {{ Request::is('sacco/standing_order*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-redo-alt"></i>
                                <p>Standing Orders</p>
                            </a>
                        </li>
                        @endcan
                        @can('sacco.cheques.index')
                        <li class="nav-item">
                            <a href="{{ url('sacco/cheque') }}"
                               class="nav-link {{ Request::is('sacco/cheque*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-money-check"></i>
                                <p>Cheque Processing</p>
                            </a>
                        </li>
                        @endcan
                        @can('sacco.atm.index')
                        <li class="nav-item">
                            <a href="{{ url('sacco/atm') }}"
                               class="nav-link {{ Request::is('sacco/atm*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-credit-card"></i>
                                <p>ATM Transactions</p>
                            </a>
                        </li>
                        @endcan
                        @can('sacco.exit.index')
                        <li class="nav-item">
                            <a href="{{ url('sacco/exit') }}"
                               class="nav-link {{ Request::is('sacco/exit*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-door-open"></i>
                                <p>Exit Settlement</p>
                            </a>
                        </li>
                        @endcan
                    </ul>
                </li>

                {{-- ══════════════════════════════════════════════════════════
                     7. E-CHANNELS
                     M-Pesa C2B · B2C · Pesalink · Agency · Cards ·
                     Cross-Sell · Communication
                ══════════════════════════════════════════════════════════ --}}
                @php
                    $echannelsActive = Request::is(
                        'sacco/mpesa*','sacco/pesalink*','sacco/agency*',
                        'sacco/cards*','sacco/cross_sell*','communication*'
                    );
                @endphp
                <li class="nav-item has-treeview {{ $echannelsActive ? 'menu-open' : '' }}">
                    <a href="#" class="nav-link {{ $echannelsActive ? 'active' : '' }}">
                        <i class="nav-icon fas fa-mobile-alt"></i>
                        <p>E-channels <i class="right fas fa-angle-left"></i></p>
                    </a>
                    <ul class="nav nav-treeview">
                        @can('sacco.mpesa_c2b.index')
                        <li class="nav-item">
                            <a href="{{ url('sacco/mpesa_c2b') }}"
                               class="nav-link {{ Request::is('sacco/mpesa_c2b*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-arrow-alt-circle-down"></i>
                                <p>M-Pesa C2B</p>
                            </a>
                        </li>
                        @endcan
                        @can('sacco.mpesa_b2c.index')
                        <li class="nav-item">
                            <a href="{{ url('sacco/mpesa_b2c') }}"
                               class="nav-link {{ Request::is('sacco/mpesa_b2c*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-arrow-alt-circle-up"></i>
                                <p>M-Pesa B2C</p>
                            </a>
                        </li>
                        @endcan
                        @can('sacco.pesalink.index')
                        <li class="nav-item">
                            <a href="{{ url('sacco/pesalink') }}"
                               class="nav-link {{ Request::is('sacco/pesalink*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-link"></i>
                                <p>Pesalink</p>
                            </a>
                        </li>
                        @endcan
                        @can('sacco.agency.index')
                        <li class="nav-item">
                            <a href="{{ url('sacco/agency') }}"
                               class="nav-link {{ Request::is('sacco/agency*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-store"></i>
                                <p>Agency Banking</p>
                            </a>
                        </li>
                        @endcan
                        @can('sacco.cards.index')
                        <li class="nav-item">
                            <a href="{{ url('sacco/cards') }}"
                               class="nav-link {{ Request::is('sacco/cards*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-sim-card"></i>
                                <p>Debit Cards</p>
                            </a>
                        </li>
                        @endcan
                        @can('sacco.cross_sell.index')
                        <li class="nav-item">
                            <a href="{{ url('sacco/cross_sell') }}"
                               class="nav-link {{ Request::is('sacco/cross_sell*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-bullhorn"></i>
                                <p>Cross-Sell</p>
                            </a>
                        </li>
                        @endcan
                        @can('communication.campaigns.index')
                        <li class="nav-item">
                            <a href="{{ url('communication/campaign') }}"
                               class="nav-link {{ Request::is('communication*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-envelope"></i>
                                <p>SMS / Communication</p>
                            </a>
                        </li>
                        @endcan
                    </ul>
                </li>

                {{-- ══════════════════════════════════════════════════════════
                     8. CHAMA / MERRY-GO-ROUND
                ══════════════════════════════════════════════════════════ --}}
                @php
                    $chamaActive = Request::is('sacco/group*','sacco/meeting*');
                @endphp
                <li class="nav-item has-treeview {{ $chamaActive ? 'menu-open' : '' }}">
                    <a href="#" class="nav-link {{ $chamaActive ? 'active' : '' }}">
                        <i class="nav-icon fas fa-users-cog"></i>
                        <p>CHAMA <i class="right fas fa-angle-left"></i></p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="{{ url('sacco/group') }}"
                               class="nav-link {{ Request::is('sacco/group*') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Chama Groups</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ url('sacco/meeting') }}"
                               class="nav-link {{ Request::is('sacco/meeting*') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Chama Meetings</p>
                            </a>
                        </li>
                    </ul>
                </li>

                {{-- ══════════════════════════════════════════════════════════
                     9. DATA UPLOAD & VALIDATION
                ══════════════════════════════════════════════════════════ --}}
                @php
                    $dataActive = Request::is('sacco/import*','sacco/integration*');
                @endphp
                <li class="nav-item has-treeview {{ $dataActive ? 'menu-open' : '' }}">
                    <a href="#" class="nav-link {{ $dataActive ? 'active' : '' }}">
                        <i class="nav-icon fas fa-upload"></i>
                        <p>Data Upload &amp; Validation <i class="right fas fa-angle-left"></i></p>
                    </a>
                    <ul class="nav nav-treeview">
                        @can('sacco.import.create')
                        <li class="nav-item">
                            <a href="{{ url('sacco/import') }}"
                               class="nav-link {{ Request::is('sacco/import*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-file-import"></i>
                                <p>Bulk Import</p>
                            </a>
                        </li>
                        @endcan
                        @can('sacco.integrations.index')
                        <li class="nav-item">
                            <a href="{{ url('sacco/integration') }}"
                               class="nav-link {{ Request::is('sacco/integration*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-plug"></i>
                                <p>Integrations</p>
                            </a>
                        </li>
                        @endcan
                    </ul>
                </li>

                {{-- ══════════════════════════════════════════════════════════
                     10. HR & PAYROLL
                ══════════════════════════════════════════════════════════ --}}
                @php
                    $payrollActive = Request::is('payroll*');
                @endphp
                <li class="nav-item has-treeview {{ $payrollActive ? 'menu-open' : '' }}">
                    <a href="#" class="nav-link {{ $payrollActive ? 'active' : '' }}">
                        <i class="nav-icon fas fa-users-cog"></i>
                        <p>HR &amp; Payroll <i class="right fas fa-angle-left"></i></p>
                    </a>
                    <ul class="nav nav-treeview">
                        @can('payroll.payroll.index')
                        <li class="nav-item">
                            <a href="{{ url('payroll') }}"
                               class="nav-link {{ Request::is('payroll') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-list-alt"></i>
                                <p>View Payroll</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ url('payroll/run') }}"
                               class="nav-link {{ Request::is('payroll/run*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-play-circle"></i>
                                <p>Payroll Runs</p>
                            </a>
                        </li>
                        @endcan
                        @can('payroll.payroll.employees.index')
                        <li class="nav-item">
                            <a href="{{ url('payroll/employee') }}"
                               class="nav-link {{ Request::is('payroll/employee*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-id-badge"></i>
                                <p>Employees</p>
                            </a>
                        </li>
                        @endcan
                        @can('payroll.payroll.items.index')
                        <li class="nav-item">
                            <a href="{{ url('payroll/item') }}"
                               class="nav-link {{ Request::is('payroll/item*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-tasks"></i>
                                <p>Payroll Items</p>
                            </a>
                        </li>
                        @endcan
                        @can('payroll.payroll.templates.index')
                        <li class="nav-item">
                            <a href="{{ url('payroll/template') }}"
                               class="nav-link {{ Request::is('payroll/template*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-file-invoice"></i>
                                <p>Payroll Templates</p>
                            </a>
                        </li>
                        @endcan
                    </ul>
                </li>

                {{-- ══════════════════════════════════════════════════════════
                     10. MY APPROVALS
                ══════════════════════════════════════════════════════════ --}}
                @php
                    $approvalsActive = Request::is('sacco/approval*','sacco/compliance*','sacco/aml*','sacco/regulatory_approvals*');
                @endphp
                <li class="nav-item has-treeview {{ $approvalsActive ? 'menu-open' : '' }}">
                    <a href="#" class="nav-link {{ $approvalsActive ? 'active' : '' }}">
                        <i class="nav-icon fas fa-clipboard-check"></i>
                        <p>My Approvals <i class="right fas fa-angle-left"></i></p>
                    </a>
                    <ul class="nav nav-treeview">
                        @can('sacco.checker.approve')
                        <li class="nav-item">
                            <a href="{{ url('sacco/approval') }}"
                               class="nav-link {{ Request::is('sacco/approval*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-check-double"></i>
                                <p>Pending Approvals</p>
                            </a>
                        </li>
                        @endcan
                        @can('sacco.governance.resolutions')
                        <li class="nav-item">
                            <a href="{{ url('sacco/governance/regulatory-approvals') }}"
                               class="nav-link {{ Request::is('sacco/governance/regulatory*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-stamp"></i>
                                <p>Regulatory Approvals</p>
                            </a>
                        </li>
                        @endcan
                        @can('sacco.compliance.dashboard')
                        <li class="nav-item">
                            <a href="{{ url('sacco/compliance') }}"
                               class="nav-link {{ Request::is('sacco/compliance*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-shield-alt"></i>
                                <p>Compliance Dashboard</p>
                            </a>
                        </li>
                        @endcan
                        @can('sacco.aml.index')
                        <li class="nav-item">
                            <a href="{{ url('sacco/aml') }}"
                               class="nav-link {{ Request::is('sacco/aml*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-exclamation-triangle"></i>
                                <p>AML Alerts</p>
                            </a>
                        </li>
                        @endcan
                    </ul>
                </li>

                {{-- ══════════════════════════════════════════════════════════
                     11. STAFF SELF SERVICE
                ══════════════════════════════════════════════════════════ --}}
                @php
                    $selfActive = Request::is('user/profile*');
                @endphp
                <li class="nav-item has-treeview {{ $selfActive ? 'menu-open' : '' }}">
                    <a href="#" class="nav-link {{ $selfActive ? 'active' : '' }}">
                        <i class="nav-icon fas fa-user-tie"></i>
                        <p>Staff Self Service <i class="right fas fa-angle-left"></i></p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="{{ url('user/profile') }}"
                               class="nav-link {{ Request::is('user/profile') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-user-circle"></i>
                                <p>My Profile</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ url('user/profile/change_password') }}"
                               class="nav-link {{ Request::is('user/profile/change_password') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-key"></i>
                                <p>Change Password</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ url('user/profile/notification') }}"
                               class="nav-link {{ Request::is('user/profile/notification*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-bell"></i>
                                <p>My Notifications</p>
                            </a>
                        </li>
                    </ul>
                </li>

                {{-- ══════════════════════════════════════════════════════════
                     12. ADMINISTRATION
                     Users · Settings · Modules · Themes ·
                     SASRA Reports · Governance · Period End
                ══════════════════════════════════════════════════════════ --}}
                @php
                    $adminActive = Request::is(
                        'user*','setting*','module*','theme*',
                        'sacco/regulatory*','sacco/governance*','sacco/period_end*',
                        'menu*','sacco/detail*'
                    );
                @endphp
                <li class="nav-item has-treeview {{ $adminActive ? 'menu-open' : '' }}">
                    <a href="#" class="nav-link {{ $adminActive ? 'active' : '' }}">
                        <i class="nav-icon fas fa-cog"></i>
                        <p>Administration <i class="right fas fa-angle-left"></i></p>
                    </a>
                    <ul class="nav nav-treeview">
                        @can('user.users.index')
                        <li class="nav-item">
                            <a href="{{ url('user') }}"
                               class="nav-link {{ Request::is('user') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-users"></i>
                                <p>Manage Users</p>
                            </a>
                        </li>
                        @endcan
                        @can('user.roles.index')
                        <li class="nav-item">
                            <a href="{{ url('user/role') }}"
                               class="nav-link {{ Request::is('user/role*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-user-shield"></i>
                                <p>Roles &amp; Permissions</p>
                            </a>
                        </li>
                        @endcan
                        @can('setting.setting.index')
                        <li class="nav-item">
                            <a href="{{ url('setting/organisation') }}"
                               class="nav-link {{ Request::is('setting/organisation*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-sliders-h"></i>
                                <p>Settings</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ url('setting/general') }}"
                               class="nav-link {{ Request::is('setting/general*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-cogs"></i>
                                <p>General Settings</p>
                            </a>
                        </li>
                        @endcan
                        @can('sacco.regulatory.index')
                        <li class="nav-item">
                            <a href="{{ url('sacco/regulatory') }}"
                               class="nav-link {{ Request::is('sacco/regulatory') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-file-contract"></i>
                                <p>SASRA Reports</p>
                            </a>
                        </li>
                        @endcan
                        @can('sacco.governance.licenses')
                        <li class="nav-item">
                            <a href="{{ url('sacco/governance/licenses') }}"
                               class="nav-link {{ Request::is('sacco/governance*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-gavel"></i>
                                <p>Governance</p>
                            </a>
                        </li>
                        @endcan
                        @can('sacco.period_end.index')
                        <li class="nav-item">
                            <a href="{{ url('sacco/period_end') }}"
                               class="nav-link {{ Request::is('sacco/period_end*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-calendar-check"></i>
                                <p>Period End</p>
                            </a>
                        </li>
                        @endcan
                        @can('core.modules.index')
                        <li class="nav-item">
                            <a href="{{ url('module') }}"
                               class="nav-link {{ Request::is('module*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-puzzle-piece"></i>
                                <p>Modules</p>
                            </a>
                        </li>
                        @endcan
                        @can('core.themes.index')
                        <li class="nav-item">
                            <a href="{{ url('theme') }}"
                               class="nav-link {{ Request::is('theme*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-palette"></i>
                                <p>Themes</p>
                            </a>
                        </li>
                        @endcan
                        @can('core.menu.index')
                        <li class="nav-item">
                            <a href="{{ url('menu') }}"
                               class="nav-link {{ Request::is('menu*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-bars"></i>
                                <p>Manage Menu</p>
                            </a>
                        </li>
                        @endcan
                        @can('activitylog.activity_logs.index')
                        <li class="nav-item">
                            <a href="{{ url('activity_log') }}"
                               class="nav-link {{ Request::is('activity_log*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-history"></i>
                                <p>Audit Log</p>
                            </a>
                        </li>
                        @endcan
                        @can('sacco.details.edit')
                        <li class="nav-item">
                            <a href="{{ url('sacco/detail/edit') }}"
                               class="nav-link {{ Request::is('sacco/detail*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-globe"></i>
                                <p>Homepage Settings</p>
                            </a>
                        </li>
                        @endcan
                    </ul>
                </li>

            </ul>
        </nav>
    </div>
</aside>
