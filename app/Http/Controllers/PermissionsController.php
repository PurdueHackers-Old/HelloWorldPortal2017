<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;

class PermissionsController extends Controller
{
  /**
   * Checks the logged in user to make sure they have
   * the role with the specified name.
   * Returns true if the user has the role, false otherwise.
   */
  public static function hasRole($roleName) {
      $user = Auth::user();
      if($user == null || count($user->roles()->where('name',$roleName)->get()) == 0) {
          return false;
      }
      return true;
  }

  /**
   * Checks the logged in user to make sure they have
   * a verified email.
   * Returns true if the user has verified their email, false otherwise.
   */
  public static function hasVerifiedEmail() {
      $user = Auth::user();
      if($user == null || $user->hasVerifiedEmail() == false) {
          return false;
      }
      return true;
  }
}
