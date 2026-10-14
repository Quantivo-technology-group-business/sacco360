<?php

Route::prefix('sacco')->group(function () {
    Route::get('search', 'GlobalSearchController@index');
    Route::get('search/ajax', 'GlobalSearchController@ajax');
    Route::get('appraisal/calculate', 'AppraisalController@calculate');

    Route::prefix('approval')->group(function () {
        Route::get('/', 'PendingApprovalController@index');
        Route::get('{id}/approve', 'PendingApprovalController@approve');
        Route::post('{id}/decline', 'PendingApprovalController@decline');
    });

    Route::prefix('transfer')->group(function () {
        Route::get('/', 'TransferController@index');
        Route::get('create', 'TransferController@create');
        Route::post('store', 'TransferController@store');
        Route::get('{id}/show', 'TransferController@show');
    });

    Route::prefix('share-transfer')->group(function () {
        Route::get('/', 'ShareTransferApprovalController@index');
        Route::get('{id}/show', 'ShareTransferApprovalController@show');
        Route::post('{id}/approve', 'ShareTransferApprovalController@approve');
        Route::post('{id}/reject', 'ShareTransferApprovalController@reject');
    });

    Route::prefix('dividend')->group(function () {
        Route::get('/', 'DividendController@index');
        Route::get('create', 'DividendController@create');
        Route::post('store', 'DividendController@store');
        Route::get('{id}/show', 'DividendController@show');
        Route::get('{id}/process', 'DividendController@process');
    });

    Route::prefix('checkoff')->group(function () {
        Route::get('/', 'CheckOffController@index');
        Route::get('create', 'CheckOffController@create');
        Route::post('store', 'CheckOffController@store');
        Route::post('sync-payroll', 'CheckOffController@syncPayroll');
        Route::get('{id}/show', 'CheckOffController@show');
        Route::get('{id}/post', 'CheckOffController@post');
        Route::get('item/{itemId}/refund', 'CheckOffController@refund');
    });

    Route::prefix('exit')->group(function () {
        Route::get('/', 'ExitSettlementController@index');
        Route::get('create', 'ExitSettlementController@create');
        Route::get('calculate', 'ExitSettlementController@calculate');
        Route::post('store', 'ExitSettlementController@store');
        Route::get('{id}/show', 'ExitSettlementController@show');
        Route::get('{id}/approve', 'ExitSettlementController@approve');
        Route::get('{id}/process', 'ExitSettlementController@process');
    });

    Route::prefix('fixed_deposit')->group(function () {
        Route::get('/', 'FixedDepositController@index');
        Route::get('create', 'FixedDepositController@create');
        Route::post('store', 'FixedDepositController@store');
        Route::get('{id}/show', 'FixedDepositController@show');
    });

    Route::get('mpesa_c2b', 'MpesaC2bController@index');
    Route::get('mpesa_b2c', 'MpesaB2cController@index');

    Route::prefix('agency')->group(function () {
        Route::get('/', 'AgencyBankingController@index');
        Route::get('dashboard', 'AgencyBankingController@dashboard');
        Route::get('create', 'AgencyBankingController@create');
        Route::post('store', 'AgencyBankingController@store');
        Route::get('{id}/show', 'AgencyBankingController@show');
        Route::get('{id}/edit', 'AgencyBankingController@edit');
        Route::post('{id}/update', 'AgencyBankingController@update');
        Route::post('{id}/suspend', 'AgencyBankingController@suspend');
        Route::get('{id}/reactivate', 'AgencyBankingController@reactivate');
        Route::get('{id}/close', 'AgencyBankingController@close');
        Route::get('transactions', 'AgencyBankingController@transactions');
        Route::get('transactions/export', 'AgencyBankingController@exportTransactions');
        Route::post('transaction/{transactionId}/reverse', 'AgencyBankingController@reverse');
        Route::post('topup', 'AgencyBankingController@topUp');
        Route::post('withdraw-float', 'AgencyBankingController@withdrawFloat');
        Route::post('deposit', 'AgencyBankingController@deposit');
        Route::post('withdraw', 'AgencyBankingController@withdraw');
        Route::get('ajax/{id}', 'AgencyBankingController@ajaxAgentInfo');
        
        // Agent portal management
        Route::get('{id}/reset-pin', 'AgentManagementController@resetPin');
        Route::get('{id}/unlock', 'AgentManagementController@unlockAgent');
        Route::get('commission-rates', 'AgentManagementController@commissionRates');
        Route::post('commission-rates/store', 'AgentManagementController@storeCommissionRate');
        Route::post('commission-rates/{id}/update', 'AgentManagementController@updateCommissionRate');
    });

    // Agent KYC
    Route::prefix('agent-kyc')->group(function () {
        Route::get('/', 'AgentKycController@index');
        Route::get('search', 'AgentKycController@search');
        Route::get('requirements', 'AgentKycController@requirements');
        Route::post('requirements/store', 'AgentKycController@storeRequirement');
        Route::post('requirements/{id}/update', 'AgentKycController@updateRequirement');
        Route::get('requirements/{id}/delete', 'AgentKycController@deleteRequirement');
        Route::get('{id}/show', 'AgentKycController@show');
        Route::post('{id}/document', 'AgentKycController@storeDocument');
        Route::get('document/{documentId}/approve', 'AgentKycController@approveDocument');
        Route::post('document/{documentId}/reject', 'AgentKycController@rejectDocument');
        Route::post('{id}/approve', 'AgentKycController@approveKyc');
        Route::post('{id}/reject', 'AgentKycController@rejectKyc');
    });

    Route::prefix('cards')->group(function () {
        // Listing & create
        Route::get('/',       'CardController@index');
        Route::get('create',  'CardController@create');
        Route::post('store',  'CardController@store');

        // Card requests
        Route::get('requests',                    'CardController@requests');
        Route::get('requests/create',             'CardController@createRequest');
        Route::post('requests/store',             'CardController@storeRequest');
        Route::get('requests/{id}/approve',       'CardController@approveRequest');
        Route::post('requests/{id}/reject',       'CardController@rejectRequest');
        Route::get('client/{clientId}/details',   'CardController@getClientDetails');

        // Individual card routes
        Route::get('{id}/show',                   'CardController@show');
        Route::post('{id}/block',                 'CardController@block');
        Route::post('{id}/activate',              'CardController@activate');
        Route::post('{id}/cancel',                'CardController@cancel');
        Route::post('{id}/lost',                  'CardController@reportLost');

        // Limits & channels
        Route::post('{id}/limits',                'CardController@updateLimits');
        Route::post('{id}/channels',              'CardController@updateChannels');

        // Delivery
        Route::post('{id}/delivery',              'CardController@updateDelivery');

        // PIN management
        Route::post('{id}/pin/set',               'CardController@setPinSet');
        Route::post('{id}/pin/reset',             'CardController@resetPin');
        Route::post('{id}/pin/unlock',            'CardController@unlockPin');

        // Transactions
        Route::get('{id}/transactions',           'CardController@transactions');
        Route::post('{id}/transactions/record',   'CardController@recordTransaction');

        // Disputes
        Route::get('{id}/disputes',               'CardController@disputes');
        Route::post('{id}/disputes/store',        'CardController@storeDispute');
        Route::post('{id}/disputes/{disputeId}/resolve', 'CardController@resolveDispute');
    });

    Route::prefix('pesalink')->group(function () {
        Route::get('/', 'PesalinkController@index');
        Route::get('create', 'PesalinkController@create');
        Route::post('store', 'PesalinkController@store');
    });

    Route::prefix('cross_sell')->group(function () {
        Route::get('/', 'CrossSellController@index');
        Route::get('create', 'CrossSellController@create');
        Route::post('store', 'CrossSellController@store');
    });

    Route::prefix('teller')->group(function () {
        Route::get('/', 'TellerController@index');
        Route::post('open', 'TellerController@openSession');
        Route::post('close', 'TellerController@closeSession');
        Route::post('limits', 'TellerController@updateLimits');
        Route::get('member-lookup', 'TellerController@memberLookup');
        Route::get('ledger', 'TellerDeskController@ledger');
        Route::post('client-panel', 'TellerDeskController@clientPanel');
        Route::post('deposit', 'TellerDeskController@receiveDeposit');
        Route::post('withdrawal', 'TellerDeskController@processWithdrawal');
        Route::post('repayment', 'TellerDeskController@receiveLoanRepayment');
        Route::get('receipt/savings/{id}', 'TellerDeskController@receiptSavings');
        Route::get('receipt/withdrawal/{id}', 'TellerDeskController@receiptWithdrawal');
        Route::get('receipt/loan/{id}', 'TellerDeskController@receiptLoan');
    });

    Route::prefix('earmark')->group(function () {
        Route::get('/', 'EarmarkController@index');
        Route::get('create', 'EarmarkController@create');
        Route::post('store', 'EarmarkController@store');
        Route::get('{id}/revoke', 'EarmarkController@revoke');
        Route::get('client/{clientId}/ajax', 'EarmarkController@ajaxForClient');
    });

    Route::prefix('reconciliation')->group(function () {
        Route::get('/', 'BankReconciliationController@index');
        Route::get('create', 'BankReconciliationController@create');
        Route::post('store', 'BankReconciliationController@store');
        Route::get('{id}/show', 'BankReconciliationController@show');
        Route::get('{id}/auto-match', 'BankReconciliationController@autoMatch');
    });


    // ── M-Pesa / Mobile Money Reconciliation ──────────────────────────────────
    Route::prefix('mpesa-recon')->group(function () {
        Route::get('dashboard',              'MpesaReconciliationController@dashboard');
        Route::get('/',                      'MpesaReconciliationController@index');
        Route::get('create',                 'MpesaReconciliationController@create');
        Route::post('store',                 'MpesaReconciliationController@store');
        Route::get('{id}/show',              'MpesaReconciliationController@show');
        Route::get('{id}/audit-log',         'MpesaReconciliationController@auditLog');
        Route::get('{id}/exceptions',        'MpesaReconciliationController@exceptions');
        Route::post('{id}/import',           'MpesaReconciliationController@importItems');
        Route::get('{id}/auto-match',        'MpesaReconciliationController@autoMatch');
        Route::post('{id}/manual-match',     'MpesaReconciliationController@manualMatch');
        Route::get('{id}/item/{itemId}/unmatch',          'MpesaReconciliationController@unmatch');
        Route::post('{id}/item/{itemId}/flag-exception',  'MpesaReconciliationController@flagException');
        Route::post('{id}/item/{itemId}/resolve',         'MpesaReconciliationController@resolveException');
        Route::get('{id}/approve',           'MpesaReconciliationController@approve');
        Route::get('{id}/archive',           'MpesaReconciliationController@archive');
        // Float accounts
        Route::get('float',                  'MpesaReconciliationController@floatAccounts');
        Route::post('float/store',           'MpesaReconciliationController@storeFloat');
        Route::post('float/{accountId}/entry','MpesaReconciliationController@addFloatEntry');
    });

    Route::get('atm', 'AtmController@index');

    Route::prefix('standing_order')->group(function () {
        Route::get('/', 'StandingOrderController@index');
        Route::get('create', 'StandingOrderController@create');
        Route::post('store', 'StandingOrderController@store');
        Route::get('accounts', 'StandingOrderController@getClientAccounts');
    });

    Route::prefix('cheque')->group(function () {
        Route::get('/', 'ChequeController@index');
        Route::get('create', 'ChequeController@create');
        Route::post('store', 'ChequeController@store');
        Route::get('{id}/present', 'ChequeController@present');
        Route::get('{id}/clear', 'ChequeController@clear');
        Route::get('{id}/bounce', 'ChequeController@bounce');
    });

    Route::get('compliance', 'ComplianceController@index');
    Route::get('compliance/ratios', 'ComplianceController@ratios');
    Route::get('compliance/borrowings', 'BorrowingController@index');
    Route::post('compliance/borrowings/store', 'BorrowingController@store');
    Route::get('compliance/audit-log', 'ComplianceAuditLogController@index');

    Route::prefix('regulatory')->group(function () {
        Route::get('/', 'RegulatoryController@index');
        Route::get('export', 'RegulatoryController@export');
        Route::get('caels', 'ClassificationController@index');
        Route::post('classification/override', 'ClassificationController@override');
        Route::get('templates', 'ReportTemplateController@index');
        Route::post('templates/store', 'ReportTemplateController@store');
        Route::get('provision-rates', 'ProvisionRateController@index');
        Route::post('provision-rates/store', 'ProvisionRateController@store');
        Route::post('provision-rates/{id}/update', 'ProvisionRateController@update');
    });

    Route::prefix('governance')->group(function () {
        Route::get('licenses', 'GovernanceController@licenses');
        Route::post('licenses/store', 'GovernanceController@storeLicense');
        Route::get('resolutions', 'GovernanceController@resolutions');
        Route::post('resolutions/store', 'GovernanceController@storeResolution');
        Route::get('inspections', 'GovernanceController@inspections');
        Route::post('inspections/store', 'GovernanceController@storeInspectionAction');
        Route::get('inspections/{id}/complete', 'GovernanceController@completeInspectionAction');
        Route::get('calendar', 'GovernanceController@calendar');
        Route::post('calendar/{id}/submit', 'GovernanceController@markCalendarSubmitted');
        Route::get('fit-proper', 'GovernanceController@fitProper');
        Route::post('fit-proper/store', 'GovernanceController@storeFitProper');
        Route::get('auditors', 'GovernanceController@auditors');
        Route::post('auditors/store', 'GovernanceController@storeAuditor');
        Route::get('regulatory-approvals', 'RegulatoryApprovalController@index');
        Route::post('regulatory-approvals/store', 'RegulatoryApprovalController@store');
        Route::get('regulatory-approvals/{id}/approve', 'RegulatoryApprovalController@approve');
    });

    Route::prefix('aml')->group(function () {
        Route::get('/', 'AmlController@index');
        Route::get('run', 'AmlController@runChecks');
        Route::post('{id}/review', 'AmlController@review');
    });

    Route::prefix('period_end')->group(function () {
        Route::get('/', 'PeriodEndController@index');
        Route::get('create', 'PeriodEndController@create');
        Route::post('store', 'PeriodEndController@store');
        Route::get('{id}/close', 'PeriodEndController@close');
    });

    // ── USSD Configuration & Member Management ────────────────────────────
    Route::prefix('ussd')->group(function () {
        Route::get('settings',                        'UssdSettingController@settings');
        Route::post('settings',                       'UssdSettingController@saveSettings');
        Route::get('members',                         'UssdSettingController@members');
        Route::get('sessions',                        'UssdSettingController@sessions');
        Route::post('member/{id}/enable',             'UssdSettingController@enable');
        Route::post('member/{id}/disable',            'UssdSettingController@disable');
        Route::post('member/{id}/reset-pin',          'UssdSettingController@resetPin');
        Route::post('member/{id}/set-pin',            'UssdSettingController@setPin');
        Route::post('member/{id}/set-services',       'UssdSettingController@setServices');
        Route::post('member/{id}/unlock',             'UssdSettingController@unlock');
    });

    Route::prefix('integration')->group(function () {
        Route::get('/', 'IntegrationController@index');
        Route::post('crb', 'IntegrationController@crbCheck');
        Route::post('nida', 'IntegrationController@nidaValidate');
        Route::post('rtgs', 'IntegrationController@rtgsTransfer');
        Route::post('signature', 'IntegrationController@captureSignature');
        Route::post('nida/client', 'IntegrationController@verifyClientNida');
    });

    Route::get('utility-payments', 'UtilityPaymentMonitorController@index');

    Route::prefix('import')->group(function () {
        Route::get('/', 'DataImportController@index');
        Route::post('store', 'DataImportController@store');
        Route::get('template/{type}', 'DataImportController@downloadTemplate')
            ->where('type', 'individual|organisation');
        Route::get('unseed/{batchId}/confirm', 'DataImportController@unseedConfirm');
        Route::post('unseed/{batchId}', 'DataImportController@unseed');
    });

    Route::get('detail/edit', 'SaccoDetailController@edit');
    Route::post('detail/update', 'SaccoDetailController@update');

    Route::prefix('kyc')->group(function () {
        Route::get('/', 'KycController@index');
        Route::get('get_kyc_pending', 'KycController@get_kyc_pending');
        Route::get('search', 'KycController@search');
        Route::get('requirements', 'KycController@requirements');
        Route::post('requirements', 'KycController@storeRequirement');
        Route::get('requirements/{id}/toggle', 'KycController@toggleRequirement');
        Route::get('document/{documentId}/approve', 'KycController@approveDocument');
        Route::post('document/{documentId}/reject', 'KycController@rejectDocument');
        Route::get('{clientId}/show', 'KycController@show');
        Route::post('{clientId}/document', 'KycController@storeDocument');
        Route::post('{clientId}/iprs', 'KycController@verifyIprs');
        Route::post('{clientId}/approve', 'KycController@approveKyc');
        Route::post('{clientId}/reject', 'KycController@rejectKyc');
        // CRB checks
        Route::post('{clientId}/crb', 'KycController@runCrbCheck');
        Route::get('{clientId}/crb/history', 'KycController@crbHistory');
        Route::get('crb/{checkId}/detail', 'KycController@crbDetail');
    });

    // =========================================================================
    // WELFARE / BENEVOLENT FUND
    // =========================================================================
    Route::prefix('welfare')->group(function () {
        Route::get('/', 'WelfareController@index');
        Route::get('products', 'WelfareController@products');
        Route::get('products/create', 'WelfareController@createProduct');
        Route::post('products/store', 'WelfareController@storeProduct');
        Route::get('products/{id}/edit', 'WelfareController@editProduct');
        Route::post('products/{id}/update', 'WelfareController@updateProduct');
        Route::get('claims', 'WelfareController@claims');
        Route::get('claims/create', 'WelfareController@createClaim');
        Route::post('claims/store', 'WelfareController@storeClaim');
        Route::get('claims/{id}/show', 'WelfareController@showClaim');
        Route::post('claims/{id}/approve', 'WelfareController@approveClaim');
        Route::post('claims/{id}/reject', 'WelfareController@rejectClaim');
        Route::post('claims/{id}/paid', 'WelfareController@markPaid');
        Route::get('contributions', 'WelfareController@contributions');
    });

    // =========================================================================
    // GROUP / CHAMA MANAGEMENT
    // =========================================================================
    Route::prefix('group')->group(function () {
        Route::get('/', 'GroupController@index');
        Route::get('get_groups', 'GroupController@get_groups');
        Route::get('create', 'GroupController@create');
        Route::post('store', 'GroupController@store');
        Route::get('{id}/show', 'GroupController@show');
        Route::post('{id}/add-member', 'GroupController@addMember');
        Route::get('{id}/remove-member/{memberId}', 'GroupController@removeMember');
        Route::post('{id}/post-contributions', 'GroupController@postContributions');
        Route::post('{id}/generate-schedule', 'GroupController@generateSchedule');
        Route::post('{id}/update-settings', 'GroupController@updateSettings');
        Route::post('{id}/swap-turns', 'GroupController@swapTurns');
        Route::post('{id}/skip-turn/{cycleId}', 'GroupController@skipTurn');
        Route::post('{id}/submit-bid/{cycleId}', 'GroupController@submitBid');
        Route::post('{id}/resolve-bid/{cycleId}', 'GroupController@resolveBidCycle');
        Route::post('{id}/complete-cycle/{cycleId}', 'GroupController@completeCycle');
        Route::post('{id}/disburse-cycle/{cycleId}', 'GroupController@disburseCycle');
        Route::post('{id}/penalty/{penaltyId}/waive', 'GroupController@waivePenalty');
        Route::get('{id}/contributions', 'GroupController@contributions');
        Route::get('{id}/get_contributions', 'GroupController@get_contributions');
        Route::post('{id}/member/{memberId}/role', 'GroupController@updateMemberRole');
        Route::post('{id}/contribution/{contributionId}/approve', 'GroupController@approveContribution');
        Route::post('{id}/contribution/{contributionId}/reject', 'GroupController@rejectContribution');
        Route::post('{id}/contribution/{contributionId}/reverse', 'GroupController@reverseContribution');
    });

    // =========================================================================
    // INVESTMENT PORTFOLIO
    // =========================================================================
    Route::prefix('investment')->group(function () {
        Route::get('/', 'InvestmentController@index');
        Route::get('get_investments', 'InvestmentController@get_investments');
        Route::get('create', 'InvestmentController@create');
        Route::post('store', 'InvestmentController@store');
        Route::get('{id}/show', 'InvestmentController@show');
        Route::post('{id}/valuation', 'InvestmentController@addValuation');
        Route::post('{id}/mature', 'InvestmentController@markMatured');
    });

    // =========================================================================
    // BUDGET MANAGEMENT
    // =========================================================================
    Route::prefix('budget')->group(function () {
        Route::get('/', 'BudgetController@index');
        Route::get('create', 'BudgetController@create');
        Route::post('store', 'BudgetController@store');
        Route::get('{id}/show', 'BudgetController@show');
        Route::post('{id}/line/store', 'BudgetController@storeLine');
        Route::get('{id}/vs-actual', 'BudgetController@budgetVsActual');
        Route::get('{id}/approve', 'BudgetController@approve');
        Route::get('{id}/activate', 'BudgetController@activate');
        Route::post('{id}/copy', 'BudgetController@copyPeriod');
    });

    // =========================================================================
    // MEETING / AGM MANAGEMENT
    // =========================================================================
    Route::prefix('meeting')->group(function () {
        Route::get('/', 'MeetingController@index');
        Route::get('get_meetings', 'MeetingController@get_meetings');
        Route::get('create', 'MeetingController@create');
        Route::post('store', 'MeetingController@store');
        Route::get('{id}/show', 'MeetingController@show');
        Route::post('{id}/agenda', 'MeetingController@addAgendaItem');
        Route::post('{id}/resolution', 'MeetingController@addResolution');
        Route::post('{id}/attendance', 'MeetingController@recordAttendance');
        Route::post('{id}/held', 'MeetingController@markHeld');
    });

    // =========================================================================
    // MEMBER DORMANCY
    // =========================================================================
    Route::prefix('dormancy')->group(function () {
        Route::get('/', 'DormancyController@index');
        Route::post('rule/store', 'DormancyController@storeRule');
        Route::get('members', 'DormancyController@dormantMembers');
        Route::get('run-check', 'DormancyController@runCheck');
        Route::post('{clientId}/reactivate', 'DormancyController@reactivate');
        Route::get('{clientId}/history', 'DormancyController@history');
    });

    // =========================================================================
    // GUARANTOR SUBSTITUTION
    // =========================================================================
    Route::prefix('guarantor-sub')->group(function () {
        Route::get('/', 'GuarantorSubstitutionController@index');
        Route::get('create', 'GuarantorSubstitutionController@create');
        Route::post('store', 'GuarantorSubstitutionController@store');
        Route::get('{id}/show', 'GuarantorSubstitutionController@show');
        Route::post('{id}/consent', 'GuarantorSubstitutionController@consent');
        Route::get('{id}/approve', 'GuarantorSubstitutionController@approve');
        Route::post('{id}/reject', 'GuarantorSubstitutionController@reject');
    });

    // =========================================================================
    // E-STATEMENT SCHEDULED DELIVERY
    // =========================================================================
    Route::prefix('estatement')->group(function () {
        Route::get('/', 'EStatementController@index');
        Route::get('create', 'EStatementController@create');
        Route::post('store', 'EStatementController@store');
        Route::get('logs', 'EStatementController@logs');
        Route::get('send-demand', 'EStatementController@showSendDemand');
        Route::post('send-demand', 'EStatementController@sendOnDemand');
        Route::get('run', 'EStatementController@runSchedules');
    });
});

// Legacy /teller/* URLs (older views/bookmarks omit the sacco/ prefix)
Route::prefix('teller')->group(function () {
    Route::get('/', 'TellerController@index');
    Route::post('open', 'TellerController@openSession');
    Route::post('close', 'TellerController@closeSession');
    Route::post('limits', 'TellerController@updateLimits');
    Route::get('member-lookup', 'TellerController@memberLookup');
    Route::get('ledger', 'TellerDeskController@ledger');
    Route::post('client-panel', 'TellerDeskController@clientPanel');
    Route::post('deposit', 'TellerDeskController@receiveDeposit');
    Route::post('withdrawal', 'TellerDeskController@processWithdrawal');
    Route::post('repayment', 'TellerDeskController@receiveLoanRepayment');
    Route::get('receipt/savings/{id}', 'TellerDeskController@receiptSavings');
    Route::get('receipt/withdrawal/{id}', 'TellerDeskController@receiptWithdrawal');
    Route::get('receipt/loan/{id}', 'TellerDeskController@receiptLoan');
});

// =========================================================================
// AGENT PORTAL
// =========================================================================
Route::prefix('agent')->name('agent.')->group(function () {
    // Dashboard
    Route::get('/', 'AgentPortalController@dashboard')->name('dashboard');
    Route::get('dashboard', 'AgentPortalController@dashboard')->name('dashboard.alt');

    // Member Lookup (AJAX)
    Route::get('member-lookup', 'AgentPortalController@memberLookup')->name('member.lookup');
    Route::post('member-details', 'AgentPortalController@memberDetails')->name('member.details');

    // Deposit
    Route::get('deposit', 'AgentPortalController@showDeposit')->name('deposit');
    Route::post('deposit', 'AgentPortalController@processDeposit')->name('deposit.process');

    // Withdrawal
    Route::get('withdrawal', 'AgentPortalController@showWithdrawal')->name('withdrawal');
    Route::post('withdrawal', 'AgentPortalController@processWithdrawal')->name('withdrawal.process');

    // Loan Payment
    Route::get('loan-payment', 'AgentPortalController@showLoanPayment')->name('loan_payment');
    Route::post('loan-payment', 'AgentPortalController@processLoanPayment')->name('loan_payment.process');

    // Utility Payment
    Route::get('utility-payment', 'AgentPortalController@showUtilityPayment')->name('utility_payment');
    Route::post('utility-payment', 'AgentPortalController@processUtilityPayment')->name('utility_payment.process');
    Route::get('utility-payments', 'AgentPortalController@utilityPayments')->name('utility_payments');

    // Member Registration
    Route::get('register-member', 'AgentPortalController@showRegisterMember')->name('register_member');
    Route::post('register-member', 'AgentPortalController@registerMember')->name('register_member.process');
    Route::get('member-registrations', 'AgentPortalController@memberRegistrations')->name('member_registrations');

    // Transactions & History
    Route::get('transactions', 'AgentPortalController@transactions')->name('transactions');
    Route::get('float-history', 'AgentPortalController@floatHistory')->name('float_history');

    // Commissions
    Route::get('commissions', 'AgentPortalController@commissions')->name('commissions');
    Route::post('commissions/withdraw', 'AgentPortalController@withdrawCommission')->name('commissions.withdraw');

    // Receipts
    Route::get('receipt/{transactionId}', 'AgentPortalController@receipt')->name('receipt');

    // Profile
    Route::get('profile', 'AgentPortalController@profile')->name('profile');
    Route::post('profile/update-pin', 'AgentPortalController@updatePin')->name('profile.update_pin');
});
