<?php

Route::prefix('v1/payroll')->middleware('auth:api')->group(function () {
    Route::get('/', 'Api\v1\PayrollController@index');
    Route::get('{id}/show', 'Api\v1\PayrollController@show');

    Route::prefix('employee')->group(function () {
        Route::get('/', 'Api\v1\PayrollController@employees');
        Route::get('{id}/show', 'Api\v1\PayrollController@employeeShow');
    });

    Route::prefix('run')->group(function () {
        Route::get('/', 'Api\v1\PayrollController@runs');
        Route::get('{id}/show', 'Api\v1\PayrollController@runShow');
    });
});
