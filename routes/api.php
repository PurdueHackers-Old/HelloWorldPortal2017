<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
Route::post('user/register', 'AuthController@register');
Route::post('user/auth', 'AuthController@login');

Route::group(['middleware' => 'jwt.auth'], function() {
	Route::post('user/apply', 'ApplicationController@createApplication');
	Route::get('applications', 'ApplicationController@getApplications');
});
