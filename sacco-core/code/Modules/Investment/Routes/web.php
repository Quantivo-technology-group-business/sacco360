<?php
use Illuminate\Support\Facades\Route;

// ── Investment Plans (admin config) ───────────────────────────────────────────
Route::prefix('investment/plan')->group(function () {
    Route::get('/',           'InvestmentPlanController@index');
    Route::get('create',      'InvestmentPlanController@create');
    Route::post('store',      'InvestmentPlanController@store');
    Route::get('{id}/show',   'InvestmentPlanController@show');
    Route::get('{id}/edit',   'InvestmentPlanController@edit');
    Route::post('{id}/update','InvestmentPlanController@update');
    Route::get('{id}/destroy','InvestmentPlanController@destroy');
});

// ── Member Investments ─────────────────────────────────────────────────────────
Route::prefix('investment')->group(function () {
    Route::get('/',                    'MemberInvestmentController@index');
    Route::get('create',               'MemberInvestmentController@create');
    Route::post('store',               'MemberInvestmentController@store');
    Route::get('{id}/show',            'MemberInvestmentController@show');
    Route::post('{id}/activate',       'MemberInvestmentController@activate');
    Route::post('{id}/post-profit',    'MemberInvestmentController@postProfit');
    Route::post('{id}/pay-profit',     'MemberInvestmentController@payProfit');
    Route::post('{id}/mature',         'MemberInvestmentController@mature');
    Route::post('{id}/withdraw',       'MemberInvestmentController@withdraw');
    // Calculator (AJAX)
    Route::post('calculate',           'CalculatorController@calculate');
});

// ── Reports ────────────────────────────────────────────────────────────────────
Route::prefix('investment/report')->group(function () {
    Route::get('/',          'InvestmentReportController@index');
    Route::get('portfolio',  'InvestmentReportController@portfolio');
    Route::get('maturities', 'InvestmentReportController@maturities');
    Route::get('profit-due', 'InvestmentReportController@profitDue');
});

// ── Member Portal: Investment Plans showcase + member investment view ──────────
Route::prefix('portal/investment')->middleware(['auth','portal.permission'])->group(function () {
    Route::get('/',            'PortalInvestmentController@index');        // my investments
    Route::get('plans',        'PortalInvestmentController@plans');        // all available plans
    Route::get('plans/{id}',   'PortalInvestmentController@planDetail');   // plan detail + calculator
    Route::get('{id}',         'PortalInvestmentController@show');         // single investment
});
