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
Route::post('user/requestPasswordReset', 'AuthController@sendPasswordReset');
Route::post('user/confirmPasswordReset', 'AuthController@performPasswordReset');

Route::group(['middleware' => 'jwt.auth'], function() {
	Route::post('user/apply', 'ApplicationController@createApplication');
	Route::get('applications', 'ApplicationController@getApplications');
	Route::get('applications/{application_id}', 'ApplicationController@getSingleApplication');
	Route::post('applications/{application_id}/setStatus', 'ApplicationController@setApplicationStatus');
	Route::post('sendApplicationEmails', 'ApplicationController@sendApplicationEmails');
});

//TODO- Day-Of (announcements, tech talks, checkin, etc...)
