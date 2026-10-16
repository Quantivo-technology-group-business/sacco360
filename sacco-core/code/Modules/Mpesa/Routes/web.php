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

Route::prefix('mpesa')->group(function() {
    // get_loan_intent and get_access_token were registered here but never
    // implemented on MpesaController, so both returned a 500. Nothing in the
    // codebase, any view, or any JS called them either.
    //
    // They are removed rather than stubbed:
    //  - STK push for loan repayment is already served by capture_payment below
    //    and by Sacco\Services\MpesaStkService.
    //  - Handing a Daraja OAuth access token to a browser client would expose a
    //    server-side credential; that must stay server-side.
    Route::any('capture_payment', 'MpesaController@capture_payment');
});
Route::any('webhooks/mpesa', 'MpesaController@webhook');