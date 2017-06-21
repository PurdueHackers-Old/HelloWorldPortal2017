<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
use App\Models\Announcement;
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


}
