<?php

Route::prefix('v1/portal')->middleware('auth:api')->group(function () {
    Route::get('dashboard', 'Api\v1\PortalController@dashboard');
    Route::get('profile', 'Api\v1\PortalController@profile');
    Route::get('clients', 'Api\v1\PortalController@linkedClients');
    Route::post('switch_client', 'Api\v1\PortalController@switchClient');

    Route::prefix('sacco')->group(function () {
        Route::get('transfer', 'Api\v1\SaccoPortalApiController@transferForm');
        Route::post('transfer', 'Api\v1\SaccoPortalApiController@storeTransfer');
        Route::get('transfers', 'Api\v1\SaccoPortalApiController@transferHistory');
        Route::post('open-bosa', 'Api\v1\SaccoPortalApiController@openBosa');

        Route::get('beneficiaries', 'Api\v1\SaccoPortalApiController@beneficiaries');
        Route::post('beneficiaries', 'Api\v1\SaccoPortalApiController@storeBeneficiary');
        Route::delete('beneficiaries/{id}', 'Api\v1\SaccoPortalApiController@destroyBeneficiary');

        Route::get('mpesa/sources', 'Api\v1\SaccoPortalApiController@mpesaSources');
        Route::post('mpesa/send', 'Api\v1\SaccoPortalApiController@mpesaSend');
        Route::post('mpesa/withdraw', 'Api\v1\SaccoPortalApiController@mpesaWithdraw');
        Route::get('mpesa/transactions', 'Api\v1\SaccoPortalApiController@mpesaTransactions');

        Route::get('kyc', 'Api\v1\SaccoPortalApiController@kycProfile');
        Route::post('kyc/document', 'Api\v1\SaccoPortalApiController@storeKycDocument');

        Route::get('standing_order', 'Api\v1\SaccoPortalApiController@standingOrders');
        Route::get('standing_order/create', 'Api\v1\SaccoPortalApiController@standingOrderForm');
        Route::post('standing_order', 'Api\v1\SaccoPortalApiController@storeStandingOrder');
        Route::post('standing_order/{id}/cancel', 'Api\v1\SaccoPortalApiController@cancelStandingOrder');

        Route::get('pesalink', 'Api\v1\SaccoPortalApiController@pesalinkTransactions');
        Route::get('pesalink/create', 'Api\v1\SaccoPortalApiController@pesalinkForm');
        Route::post('pesalink', 'Api\v1\SaccoPortalApiController@storePesalink');

        Route::get('cards', 'Api\v1\SaccoPortalApiController@cards');
        Route::get('deposit', 'Api\v1\SaccoPortalApiController@depositInfo');
    });

    Route::prefix('share/marketplace')->group(function () {
        Route::get('/', 'Api\v1\ShareMarketplaceApiController@index');
        Route::get('listings', 'Api\v1\ShareMarketplaceApiController@myListings');
        Route::get('listing/create', 'Api\v1\ShareMarketplaceApiController@listingForm');
        Route::post('listing', 'Api\v1\ShareMarketplaceApiController@storeListing');
        Route::post('listing/{id}/cancel', 'Api\v1\ShareMarketplaceApiController@cancelListing');
        Route::get('listing/{id}/buy', 'Api\v1\ShareMarketplaceApiController@buyListing');
        Route::post('listing/{id}/buy', 'Api\v1\ShareMarketplaceApiController@storePurchase');
        Route::get('direct/create', 'Api\v1\ShareMarketplaceApiController@directSaleForm');
        Route::post('direct', 'Api\v1\ShareMarketplaceApiController@storeDirectSale');
        Route::get('transfers', 'Api\v1\ShareMarketplaceApiController@transfers');
        Route::get('direct/{id}/accept', 'Api\v1\ShareMarketplaceApiController@acceptDirect');
        Route::post('direct/{id}/accept', 'Api\v1\ShareMarketplaceApiController@storeAcceptDirect');
    });
});
