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

Route::prefix('branch')->group(function() {
    Route::get('/', 'BranchController@index');
    Route::get('get_branches', 'BranchController@get_branches');
    Route::get('create', 'BranchController@create');
    Route::post('store', 'BranchController@store');
    Route::get('{id}/show', 'BranchController@show')->where('id', '[0-9]+');
    Route::get('{id}/edit', 'BranchController@edit')->where('id', '[0-9]+');
    Route::post('{id}/update', 'BranchController@update')->where('id', '[0-9]+');
    Route::get('{id}/destroy', 'BranchController@destroy')->where('id', '[0-9]+');
    Route::get('{id}/remove_user', 'BranchController@remove_user')->where('id', '[0-9]+');
    Route::post('{id}/add_user', 'BranchController@add_user')->where('id', '[0-9]+');
});
