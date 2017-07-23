<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
use App\Models\Application;
use App\Models\Resume;
use Auth;
use Illuminate\Validation\Rule;
use Log;
use Mail;
use Storage;
use Response;
use App\Models\User;
use Webpatser\Uuid\Uuid;
use Input;

class ApplicationController extends Controller
{
  //Get a user's own application
  public function getSelfApplications(Request $request) {
    //Do not select the internal status
    $application = Auth::user()->application()
      ->select('id','user_id','class_year','grad_year','major',
      'referral','hackathon_count','shirt_size','dietary_restrictions',
      'website','longanswer_1','longanswer_2','created_at','updated_at','status_public as status')
      ->with('resume')->first();
    if($application == null || count($application) == 0) {
      return response()->json(['message' => 'no_application'],404);
    }

    //Generate a url to the resume if present
    if(count($application->resume) > 0) {
      //There is a resume uploaded
      $application->resume->url = $application->resume->getPreSignedUrl();
    }
    return response()->json(['message' => 'success', 'application' => $application]);
  }

  //Get a single application
  public function getSingleApplication($application_id) {
    //User must be an admin to view applications
    if(!PermissionsController::hasRole('admin')) {
      return response()->json(['message' => 'insufficient_permissions'],403);
    }

    $application = Application::where('id',$application_id)->with('user')->with('resume')->first();
    if(count($application) == 0) {
      //This app does not exist
      return response()->json(['message' => 'invalid_id'],404);
    }
    //Generate a url to the resume
    if(count($application->resume) > 0) {
      //There is a resume uploaded
      $application->resume->url = $application->resume->getPreSignedUrl();
    }
    return $application;
  }

  //Gets a list of all applications
  public function getApplications() {
    //User must be an admin to view applications
    if(!PermissionsController::hasRole('admin')) {
      return response()->json(['message' => 'insufficient_permissions'],403);
    }

    return Application::with('user')->get();
  }

/**
 * Uploads the given file to S3.
 * returns true on success, false on failure
 */
  public function uploadResume($fileHandle,$application) {
    $resume = $application->resume;
    if(!$resume) {
      //Need to create the resume model
      $resume = new Resume;
      $resume->user_id = $application->user_id;
      $resume->application_id = $application->id;
      $resume->uuid = Uuid::generate();
      $resume->filename_original = $fileHandle->getClientOriginalName();
      $resume->save();
    }
    $path = $resume->getResumePath();
    Storage::put($path, file_get_contents($fileHandle));
  }

  public function validateFile($fileHandle) {
    if($fileHandle == null) {
      return false;
    }
    if($fileHandle->getMimeType() != "application/pdf") {
      return false;
    }
    return true;
  }

  //Submits a new application
  public function createApplication(Request $request) {
    $validator = Validator::make($request->all(), [
      'class_year' => 'required|in:freshman,sophomore,junior,senior',
      'grad_year' => 'required|in:2017,2018,2019,2020,2021,2022,2023,2024,2025',
      'major' => 'required',
      'referral' => 'required|in:social_media,website,flyers,class,friend,none',
      'hackathon_count' => 'required|integer',
      'shirt_size' => 'required|in:s,m,l,xl,xxl',
      'website' => 'url',
      'longanswer_1' => 'required|max:2000',
      'longanswer_2' => 'required|max:2000',
      'resume' => 'file',
    ]);

    if ($validator->fails()) {
        return response()->json(['message' => 'validation', 'errors' => $validator->errors()],400);
    }

    //Make sure user has not already applied
    if(count(Auth::user()->application) > 0) {
      return response()->json(['message' => 'application_already_exists'],400);
    }

    //Validate filetype
    if($request->resume
      && !$this->validateFile($request->file('resume'))) {
        return response()->json(['message' => 'validation', 'errors' => [
          'resume' => ['invalid filetype']
          ]],400);
    }


    $application = new Application;
    $application->uuid = Uuid::generate();
    $application->class_year = $request->class_year;
    $application->grad_year = $request->grad_year;
    $application->major = $request->major;
    $application->referral = $request->referral;
    $application->hackathon_count = $request->hackathon_count;
    $application->shirt_size = $request->shirt_size;
    $application->dietary_restrictions = $request->dietary_restrictions;
    $application->website = $request->website;
    $application->longanswer_1 = $request->longanswer_1;
    $application->longanswer_2 = $request->longanswer_2;

    $application->user_id = Auth::id();
    $application->status_internal = "pending";
    $application->status_public = "pending";
    $application->last_email_status = "none";
    $application->save();

    //Upload resume if provided
    if($request->resume) {
      $fileHandle = $request->file('resume');
      $this->uploadResume($fileHandle,$application);
    }

    //Email user a confirmation
    Auth::user()->sendConfirmApplicationEmail();

    return response()->json(['message' => 'success'],200);

  }

  //Updates an existing application
  public function updateApplication(Request $request) {
    //Validate input, but don't require any field in particular
    $validator = Validator::make($request->all(), [
      'class_year' => 'in:freshman,sophomore,junior,senior',
      'grad_year' => 'in:2017,2018,2019,2020,2021,2022,2023,2024,2025',
      'referral' => 'in:social_media,website,flyers,class,friend,none',
      'hackathon_count' => 'integer',
      'shirt_size' => 'in:s,m,l,xl,xxl',
      'website' => 'url',
      'longanswer_1' => 'max:2000',
      'longanswer_2' => 'max:2000',
      'resume' => 'file'
    ]);

    if ($validator->fails()) {
        return response()->json(['message' => 'validation', 'errors' => $validator->errors()],400);
    }


    //Make sure user has already applied
    $application = Auth::user()->application;
    if($application == null || count($application) == 0) {
      return response()->json(['message' => 'application_does_not_exist'],400);
    }

    //Validate filetype
    if($request->resume
      && !$this->validateFile($request->file('resume'))) {
        return response()->json(['message' => 'validation', 'errors' => [
          'resume' => ['invalid filetype']
          ]],400);
      }


    //Update any attributes which were provided
    $data = $request->only(['class_year', 'grad_year', 'major',
      'referral','hackathon_count','shirt_size','website',
      'dietary_restrictions','longanswer_1','longanswer_2','resume']);

    foreach($data as $key => $value) {
      if($value != null) {
        if($key == 'resume') {
          //Upload resume if provided
          if($request->resume) {
            $fileHandle = $request->file('resume');
            $this->uploadResume($fileHandle,$application);
          }
        } else {
          $application->$key = $value;
        }
      }
    }

    $application->save();
    return response()->json(['message' => 'success','application' => $application->with('resume')->get()],200);

  }

  public function setApplicationStatus(Request $request, $application_id) {
    //User must be an admin to view applications
    if(!PermissionsController::hasRole('admin')) {
      return response()->json(['message' => 'insufficient_permissions'],403);
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
        $application->status_internal = "accepted";
        break;
      case "waitlisted":
        $application->status_internal = "waitlisted";
        break;
      case "rejected":
        $application->status_internal = "rejected";
        break;
      default:
        return response()->json(['message' => 'validation', 'errors' => 'invalid_status'],400);
        break;
    }
    $application->save();
    return response()->json(['message' => 'success', 'application' => $application->with('user')->get()]);
  }


}
