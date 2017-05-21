<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
use App\Models\Application;
use Auth;

class ApplicationController extends Controller
{
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
    $application->status = "status_pending";
    $application->save();

    return response()->json(['message' => 'success'],200);

  }
}
