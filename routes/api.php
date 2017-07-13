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
	//Create & Edit Applications
	Route::get('user/application', 'ApplicationController@getSelfApplications');
	Route::post('user/apply', 'ApplicationController@createApplication');
	Route::post('user/updateApplication', 'ApplicationController@updateApplication');
	Route::post('user/rsvp', 'ApplicationController@sendRSVP');

	Route::get('applications', 'ApplicationController@getApplications');
	Route::get('applications/{application_id}', 'ApplicationController@getSingleApplication');
	Route::post('applications/{application_id}/setStatus', 'ApplicationController@setApplicationStatus');


	//View Announcements and event information
	Route::get('announcements', 'AnnouncementController@getAnnouncements');
	Route::post('/announcements', 'AnnouncementController@sendAnnouncement');
	Route::delete('/announcements', 'AnnouncementController@deleteAnnouncement');

	//Exec Board features
	Route::get('/exec/checkin', 'ExecController@getCheckedInUsers');
	Route::post('/exec/checkin', 'ExecController@checkinUser');
	Route::post('/exec/publishStatus', 'ExecController@publishApplicationStatus');
});

//TODO- Day-Of (announcements, tech talks, checkin, etc...)
