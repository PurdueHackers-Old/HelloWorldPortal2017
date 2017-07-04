<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
use App\Models\Announcement;
use App\Models\Checkin;
use App\Models\User;
use Auth;
use App\Models\Application;
use Carbon\Carbon;

class ExecController extends Controller
{

/**
 * Marks all internal application status as public
 */
  public function confirmApplicationStatus(Request $request) {
    //User must be an admin to send mail
    if(!PermissionsController::hasRole('devteam')) {
      return response()->json(['message' => 'insufficient_permissions']);
    }

    $applications = Application::with('user')->get();
    foreach($applications as $app) {
      $app->status_public = $app->status_internal;
      $app->published_timestamp = Carbon::now();
      $app->save();
    }
    return response()->json(['message' => 'success']);
  }

  public function generateEmailsList(Request $request) {
    //User must be an admin to send mail
    if(!PermissionsController::hasRole('admin')) {
      return response()->json(['message' => 'insufficient_permissions']);
    }

    //Get a list of all accepted applicants
    $response = ['accepted' => [], 'rejected' => []];
    $accepted =  Application::where('status_public','accepted')->with('user')->get();
    //Build accepted list
    foreach($accepted as $acc) {
      $data = [
        'firstname' => $acc->user->firstname,
        'lastname' => $acc->user->lastname,
        'email' => $acc->user->email
      ];
      array_push($response['accepted'],$data);
    }
    $rejected =  Application::where('status_public','rejected')->with('user')->get();
    //Build rejected list
    foreach($rejected as $acc) {
      $data = [
        'firstname' => $acc->user->firstname,
        'lastname' => $acc->user->lastname,
        'email' => $acc->user->email
      ];
      array_push($response['rejected'],$data);
    }
    return response()->json($response);
  }

  public function checkinUser(Request $request) {
    //User must be an admin to check someone in
    if(!PermissionsController::hasRole('admin')) {
      return response()->json(['message' => 'insufficient_permissions']);
    }

    $validator = Validator::make($request->all(), [
      'email' => 'required|exists:users,email',
    ]);
    if ($validator->fails()) {
        return response()->json(['message' => 'validation', 'errors' => $validator->errors()],400);
    }

    //TODO- Validate application status here if needed

    //Checkin the user
    $user = User::where('email',$request->email)->first();
    $checkin = $user->checkin;
    if(count($checkin) == 0) {
      //User is just now checking in
      $checkin = new Checkin;
      $checkin->user_id = $user->id;
      $checkin->save();
      return response()->json(['message' => 'success']);
    } else {
      //User already checked in
      return response()->json(['message' => 'already_checked_in']);
    }
  }

  public function getCheckedInUsers(Request $request) {
    //User must be an admin to check someone in
    if(!PermissionsController::hasRole('admin')) {
      return response()->json(['message' => 'insufficient_permissions']);
    }
    $checkins = Checkin::with('user')->get();
    return $checkins;
  }


}
