<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
use App\Models\Application;
use Auth;
use Response;

class ApplicationController extends Controller
{
    public function createApplication(Request $request) {
      $validator = Validator::make($request->all(), [
        'sampleQuestion' => 'required|max:127',
      ]);
      if ($validator->fails()) {
          return Response::json(['message' => 'validation', 'errors' => $validator->errors()],400);
      }

      //Make sure user has not already applied
      if(count(Auth::user()->application) > 0) {
        return Response::json(['message' => 'application_already_exists'],400);
      }
      $application = new Application;
      $application->sampleQuestion = $request->sampleQuestion;
      $application->user_id = Auth::id();
      $application->status = "status_pending";
      $application->save();

      return Response::json(['message' => 'success'],200);

    }
}
