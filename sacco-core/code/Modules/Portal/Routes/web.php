<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::prefix('portal')->middleware(['auth', 'portal.permission'])->group(function () {
    Route::get('/', 'PortalController@index');
    Route::get('/dashboard', 'PortalController@index');
    //loans
    Route::prefix('loan')->group(function () {
        Route::get('/', 'LoanController@index');
        Route::get('get_loans', 'LoanController@get_loans');
        Route::get('{id}/show', 'LoanController@show');
        Route::post('switch_client', 'LoanController@switch_client');
        Route::get('transaction/{id}/show', 'LoanController@show_transaction');
        Route::get('transaction/{id}/pdf', 'LoanController@pdf_transaction');
        Route::get('transaction/{id}/print', 'LoanController@print_transaction');
        Route::get('guarantor/{id}/show', 'LoanGuarantorController@show');
        Route::get('{id}/schedule/show', 'LoanController@show_schedule');
        Route::get('{id}/schedule/pdf', 'LoanController@pdf_schedule');
        Route::get('{id}/schedule/print', 'LoanController@print_schedule');
        Route::get('application', 'LoanController@application');
        Route::get('application/create', 'LoanController@create_application');
        Route::post('application/store', 'LoanController@store_application');
        Route::get('application/{id}/destroy', 'LoanController@destroy_application');
        Route::get('{id}/repayment/create', 'LoanController@create_repayment');
        Route::post('{id}/repayment/store', 'LoanController@store_repayment');
        Route::post('{id}/repayment/stk', 'LoanController@stk_repayment');
        Route::get('repayment/{id}/edit', 'LoanController@edit_repayment');
        Route::get('repayment/{id}/reverse', 'LoanController@reverse_repayment');
        Route::post('repayment/{id}/update', 'LoanController@update_repayment');
        Route::get('repayment/{id}/destroy', 'LoanController@destroy_repayment');
    });
    //shares
    Route::prefix('share')->group(function () {
        Route::get('/', 'ShareController@index');
        Route::get('get_shares', 'ShareController@get_shares');
        Route::prefix('marketplace')->group(function () {
            Route::get('/', 'ShareMarketplaceController@index');
            Route::get('listings', 'ShareMarketplaceController@myListings');
            Route::get('listing/create', 'ShareMarketplaceController@createListing');
            Route::post('listing/store', 'ShareMarketplaceController@storeListing');
            Route::get('listing/{id}/cancel', 'ShareMarketplaceController@cancelListing');
            Route::get('listing/{id}/buy', 'ShareMarketplaceController@buyListing');
            Route::post('listing/{id}/buy', 'ShareMarketplaceController@storePurchase');
            Route::get('direct/create', 'ShareMarketplaceController@createDirectSale');
            Route::post('direct/store', 'ShareMarketplaceController@storeDirectSale');
            Route::get('transfers', 'ShareMarketplaceController@transfers');
            Route::get('direct/{id}/accept', 'ShareMarketplaceController@acceptDirect');
            Route::post('direct/{id}/accept', 'ShareMarketplaceController@storeAcceptDirect');
        });
        Route::get('{id}/show', 'ShareController@show');
    });
    //savings
    Route::prefix('savings')->group(function () {
        Route::get('/', 'SavingsController@index');
        Route::get('get_savings', 'SavingsController@get_savings');
        Route::get('{id}/show', 'SavingsController@show');
        Route::get('{id}/deposit/create', 'SavingsController@create_deposit');
        Route::post('{id}/deposit/store', 'SavingsController@store_deposit');
        Route::get('{id}/withdrawal/create', 'SavingsController@create_withdrawal');
        Route::post('{id}/withdrawal/store', 'SavingsController@store_withdrawal');
        Route::get('{id}/transaction/create', 'SavingsController@create_transaction');
        Route::post('{id}/transaction/store', 'SavingsController@store_transaction');
        Route::get('transaction/{id}/show', 'SavingsController@show_transaction');
        Route::get('transaction/{id}/pdf', 'SavingsController@pdf_transaction');
        Route::get('transaction/{id}/print', 'SavingsController@print_transaction');
    });
    //wallet
    Route::prefix('wallet')->group(function () {
        Route::get('/', 'PortalWalletController@index');
        Route::get('deposit/create', 'PortalWalletController@createDeposit');
        Route::post('deposit/store', 'PortalWalletController@storeDeposit');
        Route::get('withdraw/create', 'PortalWalletController@createWithdrawal');
        Route::post('withdraw/store', 'PortalWalletController@storeWithdrawal');
    });
    //client
    Route::prefix('client')->group(function () {
        Route::get('/', 'ClientController@index');
        Route::post('switch_client', 'ClientController@switch_client');
        Route::post('transaction-pin', 'ClientController@setTransactionPin');
    });
    // SACCO internet banking
    Route::prefix('sacco')->group(function () {
        Route::get('accounts', 'SaccoAccountsController@index');
        Route::get('statements', 'SaccoStatementController@index');
        Route::post('statements/generate', 'SaccoStatementController@generate');
        Route::get('earmarks', 'SaccoEarmarkController@index');
        Route::post('otp/request', 'SaccoOtpController@request');
        Route::get('transfer', 'SaccoTransferController@index');
        Route::post('transfer/store', 'SaccoTransferController@store');
        Route::get('transfers', 'SaccoTransferHistoryController@index');
        Route::get('member-transfer/create', 'SaccoMemberTransferController@create');
        Route::post('member-transfer/store', 'SaccoMemberTransferController@store');
        Route::post('open-bosa', 'SaccoCrossSellController@openBosa');
        Route::post('open-fosa', 'SaccoCrossSellController@openFosa');
        Route::get('kyc', 'SaccoKycController@index');
        Route::post('kyc/document', 'SaccoKycController@storeDocument');
        Route::get('cards', 'SaccoCardController@index');
        Route::post('cards/{id}/block', 'SaccoCardController@block');
        Route::get('deposit', 'SaccoDepositController@index');
        Route::prefix('utility')->group(function () {
            Route::get('/', 'SaccoUtilityPaymentController@index');
            Route::get('create', 'SaccoUtilityPaymentController@create');
            Route::post('store', 'SaccoUtilityPaymentController@store');
        });
        Route::prefix('cheques')->group(function () {
            Route::get('/', 'SaccoChequeController@index');
            Route::get('create', 'SaccoChequeController@create');
            Route::post('store', 'SaccoChequeController@store');
        });
        Route::prefix('fixed_deposit')->group(function () {
            Route::get('/', 'SaccoFixedDepositController@index');
            Route::get('create', 'SaccoFixedDepositController@create');
            Route::post('store', 'SaccoFixedDepositController@store');
        });
        Route::get('dividends', 'SaccoDividendController@index');
        Route::get('checkoff', 'SaccoCheckoffController@index');
        Route::get('atm', 'SaccoAtmHistoryController@index');
        Route::get('exit', 'SaccoExitController@index');
        Route::post('exit/store', 'SaccoExitController@store');
        Route::prefix('standing_order')->group(function () {
            Route::get('/', 'SaccoStandingOrderController@index');
            Route::get('create', 'SaccoStandingOrderController@create');
            Route::post('store', 'SaccoStandingOrderController@store');
            Route::get('accounts', 'SaccoStandingOrderController@getAccounts');
            Route::get('{id}/cancel', 'SaccoStandingOrderController@cancel');
        });
        Route::prefix('pesalink')->group(function () {
            Route::get('/', 'SaccoPesalinkController@index');
            Route::get('create', 'SaccoPesalinkController@create');
            Route::post('store', 'SaccoPesalinkController@store');
        });
        // CHAMA / GROUP portal
        Route::prefix('chama')->group(function () {
            Route::get('/', 'ChamaController@index');
            Route::get('my-rotation', 'ChamaController@myRotation');
            Route::get('meetings', 'ChamaController@meetings');
            Route::get('meetings/{id}/show', 'ChamaController@showMeeting');
            Route::get('{id}/show', 'ChamaController@show');
            Route::get('{id}/contribute', 'ChamaController@contribute');
            Route::post('{id}/contribute', 'ChamaController@storeContribution');
        });

        Route::prefix('beneficiaries')->group(function () {
            Route::get('/', 'SaccoBeneficiaryController@index');
            Route::get('create', 'SaccoBeneficiaryController@create');
            Route::post('store', 'SaccoBeneficiaryController@store');
            Route::post('{id}/destroy', 'SaccoBeneficiaryController@destroy');
        });
        Route::prefix('mpesa')->group(function () {
            Route::prefix('send')->group(function () {
                Route::get('create', 'SaccoMpesaSendController@create');
                Route::post('store', 'SaccoMpesaSendController@store');
            });
            Route::prefix('withdraw')->group(function () {
                Route::get('create', 'SaccoMpesaWithdrawController@create');
                Route::post('store', 'SaccoMpesaWithdrawController@store');
            });
            Route::get('transactions', 'SaccoMpesaB2cHistoryController@index');
        });
    });
});
