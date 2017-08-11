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
use Log;
use Storage;

class ExecController extends Controller
{

/**
 * Marks all internal application status as public
 */
  public function publishApplicationStatus(Request $request) {

    //User must be an admin to send mail
    if(!PermissionsController::hasRole('devteam')) {
      return response()->json(['message' => 'insufficient_permissions']);
    }


    $response = ['accepted' => [], 'waitlisted' => [], 'rejected' => []];
    $warningApplications = [];
    //Get a list of all applications where the status is about to change
    $updatedApplications =  Application::whereRaw("status_public != status_internal")
      ->where('status_internal','!=','pending')->with('user')->get();
    foreach($updatedApplications as $application) {
      $data = [
        'firstname' =>   $application->user->firstname,
        'lastname'  =>   $application->user->lastname,
        'email'     =>   $application->user->email,
        'status_old' =>  $application->status_public,
        'status_new' =>  $application->status_internal
      ];
      switch($application->status_internal) {
        case "accepted":
          array_push($response['accepted'],$data);
          break;

        case "waitlisted":
          array_push($response['waitlisted'],$data);
          break;

        case "rejected":
          array_push($response['rejected'],$data);
          break;
      }

      //Check for any users who were un-accepted
      if(($application->status_internal == "waitlisted"
      || $application->status_internal == "rejected") && $application->status_public == "accepted") {
        array_push($warningApplications,$application);
      }
    }

    //If any users had their status go backwards, cancel the operation and issue a warning
    if(count($warningApplications) > 0) {
      Log::warning("DID NOT publish application status due to warnings! Affected applications: " . json_encode($warningApplications));
      $filename = "/mail_logs/log_".Carbon::now()->timezone("EST")->format("Y-m-d__H_i_s").".log";
      $fileContent = "DID NOT publish application status due to warnings!";
      $fileContent .= "\nTime: ".Carbon::now()->timezone("EST")->format("Y-m-d H:i:s");
      $fileContent .= "\n\nAffected Applications:\n".json_encode($warningApplications);
      Storage::disk('local')->put($filename,$fileContent);
      return response()->json(['message' => 'warning', 'applications' =>
        $warningApplications, 'details' => "These users are about to have their status changed from accepted to waitlisted or rejected. This should not happen!"],400);
    }

    //Publish the statuses
    $applications = Application::with('user')->get();
    foreach($applications as $app) {
      $app->status_public = $app->status_internal;
      $app->published_timestamp = Carbon::now();
      $app->save();
    }

    //Log a record of this update
    Log::warning("PUBLISHING application status! Affected users: " . json_encode($response));
    $filename = "/mail_logs/log_".Carbon::now()->timezone("EST")->format("Y-m-d__H_i_s").".log";
    $fileContent = "PUBLISHING application status!";
    $fileContent .= "\nTime: ".Carbon::now()->timezone("EST")->format("Y-m-d H:i:s");
    $fileContent .= "\n\nAffected Users:\n".json_encode($response);
    Storage::disk('local')->put($filename,$fileContent);

    return response()->json(['message' => 'success','data' => $response]);
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
    $user = User::where('email',$request->email)->first();

    //Make sure user is checked in
    if(!PermissionsController::userIsAccepted($user)) {
      $validator->getMessageBag()->add('checkin', 'User must have been accepted to check in.');
      return response()->json(['message' => 'validation', 'errors' => $validator->errors()],403);
    }

    //Checkin the user
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

  public function getStatistics() {
    //User must be an admin to check someone in
    if(!PermissionsController::hasRole('admin')) {
      return response()->json(['message' => 'insufficient_permissions']);
    }

    return response()->json([
      'message' => 'success',
      'checkins' => Checkin::count(),
      'applications' => Application::count(),
      'pending_internal' => Application::where('status_internal','pending')->count(),
      'accepted_internal' => Application::where('status_internal','accepted')->count(),
      'rejected_internal' => Application::where('status_internal','rejected')->count(),
      'waitlisted_internal' => Application::where('status_internal','waitlisted')->count(),
      'pending_public' => Application::where('status_public','pending')->count(),
      'accepted_public' => Application::where('status_public','accepted')->count(),
      'rejected_public' => Application::where('status_public','rejected')->count(),
      'waitlisted_public' => Application::where('status_public','waitlisted')->count(),
    ]);
  }

  /**
   * Returns the id for an app which still needs to be reviewed by an admin
   */
  public function getNextApp() {
    return Application::where('status_internal','pending')->inRandomOrder()->first();
  }

}
