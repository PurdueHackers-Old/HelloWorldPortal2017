<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
use App\Models\Announcement;
use Auth;

class AnnouncementController extends Controller
{
  //Get a list of announcements
  public function getAnnouncements() {
    //Decide which filter to use
    //TODO- Add filter for attending/not attending / mentor/student
    $announcements = Announcement::get();
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
      'scope' => 'required|in:all,attending,mentors',
    ]);
    if ($validator->fails()) {
      return ['message' => 'validation', 'errors' => $validator->errors()];
    }
    $announcement = new Announcement;
    $announcement->user_id = Auth::id();
    $announcement->message = $request->message;
    $announcement->scope = $request->scope;
    $announcement->save();
    return response()->json(['message' => 'success']);
  }

  public function deleteAnnouncement(Request $request) {
    //User must be an admin
    if(!PermissionsController::hasRole('admin')) {
      return response()->json(['message' => 'insufficient_permissions']);
    }
    $validator = Validator::make($request->all(), [
      'message' => 'required',
      'id' => 'required|exists:announcements,id',
    ]);
    if ($validator->fails()) {
      return ['message' => 'validation', 'errors' => $validator->errors()];
    }

    Announcement::destroy($request->id);
    return response()->json(['message' => 'success']);
  }

}
