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


  //Get a user's own application
  public function getSelfApplications(Request $request) {
    $application = Auth::user()->application;
    if($application == null || count($application) == 0) {
      return response()->json(['message' => 'no_application'],404);
    }
    return response()->json(['message' => 'success', 'application' => $application]);
  }

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
      'class_year' => 'required|in:freshman,sophomore,junior,senior',
      'grad_year' => 'required|in:2017,2018,2019,2020,2021,2022,2023,2024,2025',
      'major' => 'required',
      'referral' => 'required|in:social_media,website,flyers,class,friend',
      'hackathon_count' => 'required|integer',
      'shirt_size' => 'required|in:s,m,l,xl,xxl',
      'github' => 'required|url',
      'longanswer_1' => 'required',
      'longanswer_2' => 'required',
    ]);

    if ($validator->fails()) {
        return response()->json(['message' => 'validation', 'errors' => $validator->errors()],400);
    }

    //Make sure user has not already applied
    if(count(Auth::user()->application) > 0) {
      return response()->json(['message' => 'application_already_exists'],400);
    }

    $application = new Application;

    $application->class_year = $request->class_year;
    $application->grad_year = $request->grad_year;
    $application->major = $request->major;
    $application->referral = $request->referral;
    $application->hackathon_count = $request->hackathon_count;
    $application->shirt_size = $request->shirt_size;
    $application->dietary_restrictions = $request->dietary_restrictions;
    $application->github = $request->github;
    $application->longanswer_1 = $request->longanswer_1;
    $application->longanswer_2 = $request->longanswer_2;

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
      'class_year' => 'in:freshman,sophomore,junior,senior',
      'grad_year' => 'in:2017,2018,2019,2020,2021,2022,2023,2024,2025',
      'referral' => 'in:social_media,website,flyers,class,friend',
      'hackathon_count' => 'integer',
      'shirt_size' => 'in:s,m,l,xl,xxl',
      'github' => 'url',
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
    $data = $request->only(['class_year', 'grad_year', 'major',
      'referral','hackathon_count','shirt_size','github',
      'dietary_restrictions','longanswer_1','longanswer_2']);

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
