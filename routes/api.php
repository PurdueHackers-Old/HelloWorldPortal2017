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


//Unauthenticated Routes
Route::get('announcements', 'AnnouncementController@getAnnouncements');
Route::get('announcements/{announcement_id}', 'AnnouncementController@getAnnouncement');

Route::post('user/register', 'AuthController@register');
Route::post('user/auth', 'AuthController@login');
Route::post('user/requestPasswordReset', 'AuthController@sendPasswordReset');
Route::post('user/confirmPasswordReset', 'AuthController@performPasswordReset');
Route::post('user/confirmEmail', 'AuthController@confirmVerificationEmail');

Route::post('user/interest', 'AuthController@subscribeToInterest');
Route::get('applications/mode', 'ApplicationController@getApplicationMode');


//Authenticated Routes
Route::group(['middleware' => 'jwt.auth'], function() {
	//Email Confirmation
	Route::post('user/resendVerificationEmail', 'AuthController@requestVerificationEmail');
	Route::get('user/isverified', 'AuthController@checkVerifiedEmail');

	//Create & Edit Applications
	Route::get('user/application', 'ApplicationController@getSelfApplications');
	Route::post('user/apply', 'ApplicationController@createApplication');
	Route::post('user/updateApplication', 'ApplicationController@updateApplication');

	Route::get('applications', 'ApplicationController@getApplications');
	Route::get('applications/{application_id}', 'ApplicationController@getSingleApplication');
	Route::post('applications/{application_id}/setStatus', 'ApplicationController@setApplicationStatus');

	//View Announcements and event information
	Route::post('/announcements', 'AnnouncementController@sendAnnouncement');
	Route::delete('/announcements/{announcement_id}', 'AnnouncementController@deleteAnnouncement');

	//Exec Board features
	Route::get('/exec/checkin', 'ExecController@getCheckedInUsers');
	Route::get('/exec/checkinmode', 'ExecController@getCheckinMode');
	Route::post('/exec/checkinmode', 'ExecController@updateCheckinMode');
	Route::post('/exec/checkin', 'ExecController@checkinUser');

	Route::post('/exec/removeCheckin', 'ExecController@removeCheckedInUser');
	Route::post('/exec/publishStatus', 'ExecController@publishApplicationStatus');
	Route::get('/exec/statistics', 'ExecController@getStatistics');
	Route::get('/exec/nextApplication', 'ExecController@getNextApp');
	Route::post('user/search', 'AuthController@getEmailSuggestions');
	Route::get('user/interest', 'AuthController@getInterestSignups');
});
