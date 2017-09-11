<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
use App\Models\Announcement;
use App\Models\Checkin;
use App\Models\User;
use App\Models\Resume;
use Auth;
use App\Models\Application;
use App\Models\Setting;
use Carbon\Carbon;
use Log;
use Storage;
use DB;

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

    //Check the checkin mode
    if(ExecController::getSetting("checkin_mode") == "accepted_only") {
      //The user must be accepted
      if(!PermissionsController::userIsAccepted($user)) {
        $validator->getMessageBag()->add('checkin', 'User must have been accepted to check in.');
        return response()->json(['message' => 'validation', 'errors' => $validator->errors()],403);
      }
    } else if(ExecController::getSetting("checkin_mode") == "waitlisted_okay") {
      //Waitlisted is okay too
      if(!PermissionsController::userIsAcceptedOrWaitlisted($user)) {
        $validator->getMessageBag()->add('checkin', 'User must have been accepted or waitlisted to check in.');
        return response()->json(['message' => 'validation', 'errors' => $validator->errors()],403);
      }
    } else if(ExecController::getSetting("checkin_mode") == null) {
      //There is no value defined
      ExecController::putSetting("checkin_mode","accepted_only"); //Default to accepted only
      //The user must be accepted
      if(!PermissionsController::userIsAccepted($user)) {
        $validator->getMessageBag()->add('checkin', 'User must have been accepted to check in.');
        return response()->json(['message' => 'validation', 'errors' => $validator->errors()],403);
      }
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

  public function removeCheckedInUser(Request $request) {
    //User must be an admin to update checkins
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

    //Make sure user is already checked in
    $checkin = $user->checkin;
    if(count($checkin) == 0) {
      $validator->getMessageBag()->add('checkin', 'User is not checked in');
      return response()->json(['message' => 'validation', 'errors' => $validator->errors()],403);
    }

    //Delete the checkin for this user
    $checkin->delete();
    return response()->json(['message' => 'success'],200);
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

    $resumeCount = DB::table('resumes')
      ->join('applications','resumes.application_id','applications.id')
      ->select('*')
      ->where('applications.status_internal','accepted')
      ->count();

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
      'resumes' => Resume::count(),
      'resumes_accepted' => $resumeCount,
    ]);
  }

  /**
   * Returns the id for an app which still needs to be reviewed by an admin
   */
  public function getNextApp() {
    return Application::where('status_internal','pending')->inRandomOrder()->first();
  }

  public function updateCheckinMode(Request $request) {
    //User must be an admin to check someone in
    if(!PermissionsController::hasRole('admin')) {
      return response()->json(['message' => 'insufficient_permissions']);
    }

    $validator = Validator::make($request->all(), [
      'mode' => 'required|in:accepted_only,waitlisted_okay',
    ]);
    if ($validator->fails()) {
        return response()->json(['message' => 'validation', 'errors' => $validator->errors()],400);
    }

    switch($request->mode) {
      case "accepted_only":
        ExecController::putSetting('checkin_mode',"accepted_only");
        break;
      case "waitlisted_okay":
        ExecController::putSetting('checkin_mode',"waitlisted_okay");
        break;
      default:
        return response()->json(['message' => 'unknown mode'],400);
    }
    return response()->json(['message' => 'success', 'mode' => ExecController::getSetting('checkin_mode')],200);
  }

  public function getCheckinMode() {
    //User must be an admin to see this information
    if(!PermissionsController::hasRole('admin')) {
      return response()->json(['message' => 'insufficient_permissions']);
    }

    return response()->json(['message' => 'success','mode' => ExecController::getSetting("checkin_mode")]);
  }

  public static function getSetting($settingName) {
    $setting = Setting::where('name',$settingName)->first();
    if(count($setting) < 1) {
      return null;
    } else {
      return $setting->value;
    }
  }

  public static function putSetting($settingName,$settingValue) {
    $setting = Setting::where('name',$settingName)->first();
    if(count($setting) < 1) {
      //This is a new
      $setting = new Setting;
      $setting->name = $settingName;
    }
    $setting->value = $settingValue;
    $setting->save();
  }
}
