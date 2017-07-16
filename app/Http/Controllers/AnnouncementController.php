<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
use App\Models\Announcement;
use App\Models\User;
use Auth;
use Mail;

class AnnouncementController extends Controller
{
  //Get a list of announcements
  public function getAnnouncements() {
    //Decide which filter to use
    $announcements = Announcement::get();
    return response()->json(['message' => 'success',
    'announcements' => $announcements]);
  }

  //Get a specific announcements
  public function getAnnouncement($announcement_id) {
    //Decide which filter to use
    $announcements = Announcement::where('id',$announcement_id)->get();
    if(count($announcements) <= 0) {
      return response()->json(['message' => 'error',
      'details' => 'Invalid announcement id'],404);
    }
    return response()->json(['message' => 'success',
    'announcements' => $announcements]);
  }

  //Create a new announcement
  public function sendAnnouncement(Request $request) {
    //User must be an admin
    if(!PermissionsController::hasRole('admin')) {
      return response()->json(['message' => 'insufficient_permissions']);
    }
    $validator = Validator::make($request->all(), [
      'message' => 'required',
      'should_email' => 'required|boolean',
    ]);
    if ($validator->fails()) {
      return ['message' => 'validation', 'errors' => $validator->errors()];
    }
    $announcement = new Announcement;
    $announcement->user_id = Auth::id();
    $announcement->message = $request->message;
    $announcement->save();

    if($request->should_email) {
      //Queue a mass email to anybody who's checked in
      $totalUsers = User::with('checkin')->get();
      $targetUsers = [];
      foreach($totalUsers as $u) {
        if(count($u->checkin) > 0) {
          array_push($targetUsers,$u);
        }
      }
      Mail::bcc($targetUsers)->queue(new \App\Mail\AnnouncementMail($announcement->message));
    }
    return response()->json(['message' => 'success']);
  }

  public function deleteAnnouncement($announcement_id) {
    //User must be an admin
    if(!PermissionsController::hasRole('admin')) {
      return response()->json(['message' => 'insufficient_permissions']);
    }
    $announcement = Announcement::findOrFail($announcement_id);
    $announcement->delete();
    return response()->json(['message' => 'success']);
  }

}
