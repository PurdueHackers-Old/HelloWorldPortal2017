<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
use App\Models\Application;
use Auth;
use Illuminate\Validation\Rule;
use Log;
use Mail;

class ApplicationController extends Controller
{


  //Get a single application
  public function getSingleApplication($application_id) {
    //User must be an admin to view applications
    if(!PermissionsController::hasRole('admin')) {
      return response()->json(['message' => 'insufficient_permissions']);
    }
    return Application::findOrFail($application_id)->with('user')->get();
  }

  //Gets a list of all applications
  public function getApplications() {
    //User must be an admin to view applications
    if(!PermissionsController::hasRole('admin')) {
      return response()->json(['message' => 'insufficient_permissions']);
    }

    return Application::with('user')->get();
  }

  //Submits a new application
  public function createApplication(Request $request) {
    $validator = Validator::make($request->all(), [
      'sampleQuestion' => 'required|max:127',
    ]);
    if ($validator->fails()) {
        return response()->json(['message' => 'validation', 'errors' => $validator->errors()],400);
    }

    //Make sure user has not already applied
    if(count(Auth::user()->application) > 0) {
      return response()->json(['message' => 'application_already_exists'],400);
    }
    $application = new Application;
    $application->sampleQuestion = $request->sampleQuestion;
    $application->user_id = Auth::id();
    $application->status = "pending";
    $application->last_email_status = "none";
    $application->save();

    return response()->json(['message' => 'success'],200);

  }

  public function setApplicationStatus(Request $request, $application_id) {
    //User must be an admin to view applications
    if(!PermissionsController::hasRole('admin')) {
      return response()->json(['message' => 'insufficient_permissions']);
    }

    $validator = Validator::make($request->all(), [
      'status' => [
          'required',
          Rule::in(['accepted', 'rejected','waitlisted']),
      ],
    ]);
    if ($validator->fails()) {
        return response()->json(['message' => 'validation', 'errors' => $validator->errors()],400);
    }

    $application = Application::findOrFail($application_id);
    switch($request->status) {
      case "accepted":
        $application->status = "accepted";
        break;
      case "waitlisted":
        $application->status = "waitlisted";
        break;
      case "rejected":
        $application->status = "rejected";
        break;
      default:
        return response()->json(['message' => 'validation', 'errors' => 'invalid_status'],400);
        break;
    }
    $application->save();
    return $application->with('user')->get();
  }

  public function sendApplicationEmails(Request $request) {
    //User must be an admin to view applications
    if(!PermissionsController::hasRole('admin')) {
      return response()->json(['message' => 'insufficient_permissions']);
    }

    //Grab all applications which have not been emailed out already AND that are not pending
    $applicationsToSend = Application::where('last_email_status','none')->where('status','!=','pending')->with('user')->get();
    foreach($applicationsToSend as $app) {
      $app->last_email_status = $app->status;
      $app->save();
      switch($app->status) {
        case "accepted":
          Log::debug("Sent acceptance email for application" . $app->id . " and userID: " . $app->user->id);
          Mail::to($app->user)->queue(new \App\Mail\AcceptedMail($app));
          break;
        case "waitlisted":
          Log::debug("Sent waitlist email for application" . $app->id . " and userID: " . $app->user->id);
          Mail::to($app->user)->queue(new \App\Mail\WaitlistedMail($app));
          break;
        case "rejected":
          Log::debug("Sent reject email for application" . $app->id . " and userID: " . $app->user->id);
          Mail::to($app->user)->queue(new \App\Mail\RejectedMail($app));
          break;
        default:
          Log::debug("Unknown status for app with id " . $app->id . " and status: " . $app->status);
          break;
      }
    }
    return $applicationsToSend;
  }
}
