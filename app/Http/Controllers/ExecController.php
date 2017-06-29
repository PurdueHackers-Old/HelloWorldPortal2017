<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
use App\Models\Announcement;
use App\Models\Checkin;
use App\Models\User;
use Auth;

class ExecController extends Controller
{

  public function sendApplicationEmails(Request $request) {
    //User must be an admin to send mail
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
