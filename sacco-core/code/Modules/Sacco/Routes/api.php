<?php

Route::prefix('v1/sacco')->group(function () {
    Route::post('ussd', 'UssdController@handle');
    Route::prefix('ussd/actions')->middleware('sacco.ussd.api')->group(function () {
        Route::post('transfer', 'UssdActionController@transfer');
        Route::post('loan-application', 'UssdActionController@loanApplication');
        Route::post('utility-payment', 'UssdActionController@utilityPayment');
        Route::post('mpesa-stk', 'UssdActionController@mpesaStk');
        Route::get('providers', 'UssdActionController@providers');
    });
    Route::get('search', 'GlobalSearchController@ajax');
    Route::get('member/{identifier}', 'SaccoApiController@member360');
    Route::get('appraisal', 'SaccoApiController@appraisal');
    Route::get('earmarks/{clientId}', 'SaccoApiController@earmarks');
    Route::post('atm', 'AtmController@process')->middleware('sacco.atm.api');
    Route::post('mpesa/c2b/validate', 'MpesaC2bController@validate');
    Route::post('mpesa/c2b/confirm', 'MpesaC2bController@confirm');
    Route::post('mpesa/stk/group-contribution', 'MpesaC2bController@stkGroupContribution');
    Route::post('mpesa/b2c/result', 'MpesaB2cController@result');
    Route::post('mpesa/b2c/timeout', 'MpesaB2cController@timeout');
    Route::post('pesalink/inbound', 'PesalinkController@inbound');

    Route::prefix('compliance')->middleware('auth:api')->group(function () {
        Route::get('ratios', 'ComplianceApiController@ratios');
        Route::get('reports/{form}', 'ComplianceApiController@report');
        Route::get('classifications', 'ComplianceApiController@classifications');
        Route::get('member/{clientId}/export', 'ComplianceApiController@memberExport');
    });

    Route::prefix('kyc')->middleware('auth:api')->group(function () {
        Route::get('/', 'Api\v1\KycApiController@index');
        Route::get('search', 'Api\v1\KycApiController@search');
        Route::get('requirements', 'Api\v1\KycApiController@requirements');
        Route::post('requirements', 'Api\v1\KycApiController@storeRequirement');
        Route::post('requirements/{id}/toggle', 'Api\v1\KycApiController@toggleRequirement');
        Route::get('{clientId}/show', 'Api\v1\KycApiController@show');
        Route::post('{clientId}/document', 'Api\v1\KycApiController@storeDocument');
        Route::post('{clientId}/iprs', 'Api\v1\KycApiController@verifyIprs');
        Route::post('{clientId}/approve', 'Api\v1\KycApiController@approveKyc');
        Route::post('{clientId}/reject', 'Api\v1\KycApiController@rejectKyc');
        Route::post('document/{documentId}/approve', 'Api\v1\KycApiController@approveDocument');
        Route::post('document/{documentId}/reject', 'Api\v1\KycApiController@rejectDocument');
    });

    Route::prefix('transfer')->middleware('auth:api')->group(function () {
        Route::get('/', 'Api\v1\TransferApiController@index');
        Route::get('create', 'Api\v1\TransferApiController@create');
        Route::post('store', 'Api\v1\TransferApiController@store');
        Route::get('{id}/show', 'Api\v1\TransferApiController@show');
    });

    // Agency Banking API
    Route::prefix('agency')->group(function () {
        Route::get('agent/{agentNumber}', 'Api\v1\AgencyApiController@findAgent');
        Route::post('withdrawal/initiate', 'Api\v1\AgencyApiController@initiateWithdrawal')->middleware('auth:api');
        Route::post('withdrawal/confirm', 'Api\v1\AgencyApiController@confirmWithdrawal')->middleware('auth:api');
    });
});
