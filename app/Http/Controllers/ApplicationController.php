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

  //Updates an existing application
  public function updateApplication(Request $request) {
    //Validate input, but don't require any field in particular
    $validator = Validator::make($request->all(), [
      'sampleQuestion' => 'max:127',
    ]);
    
    if ($validator->fails()) {
        return response()->json(['message' => 'validation', 'errors' => $validator->errors()],400);
    }


    //Make sure user has already applied
    $application = Auth::user()->application;
    if($application == null || count($application) == 0) {
      return response()->json(['message' => 'application_does_not_exist'],400);
    }

    //Update any attributes which were provided
    $data = $request->only(['sampleQuestion']);
    foreach($data as $key => $value) {
      if($value != null) {
        $application->$key = $value;
      }
    }

    $application->save();
    return response()->json(['message' => 'success','application' => $application],200);

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


}
