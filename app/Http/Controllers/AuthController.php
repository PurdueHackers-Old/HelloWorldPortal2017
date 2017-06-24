<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
use App\Models\User;
use Hash;
use Auth;
use App\Models\Role;
use App\Models\PasswordReset;
use Carbon\Carbon;

class AuthController extends Controller
{
  /**
  * Authenticate a user
  *
  * @param  Request  $request
  * @return Response
  */
  public function login(Request $request) {
    $credentials = $request->only('email', 'password');
    $validator = Validator::make($credentials, [
      'email' => 'required|max:127',
      'password'   => 'required',
    ]);
    if ($validator->fails()) {
      return ['message' => 'validation', 'errors' => $validator->errors()];
    }
    $email = $request->email;
    if (Auth::attempt(['email' => $email, 'password' => $request->password])) {
      $token = Auth::user()->getToken();
      return ['message' => 'success', 'token' => $token];
    }
    return response()->json(['message' => 'invalid_credentials'], 401);
  }

  /**
  * Register a user
  *
  * @param  Request  $request
  * @return Response
  */
  public function register(Request $request) {
    $validator = Validator::make($request->all(), [
      'firstname' => 'required',
      'lastname' => 'required',
      'email'   => 'required|email|unique:users',
      'password'    => 'required',
    ]);
    if ($validator->fails()) {
      return ['message' => 'validation', 'errors' => $validator->errors()];
    }

    $baseRole = Role::where('name','user')->first();
    if(count($baseRole) == 0) {
      return response()->json(['message' => 'internal_role_error'], 500);
    }

    //Require people to register with a purdue email 
    if(strpos($request->email,"@purdue.edu") === false) {
      return response()->json(['message' => 'You must register using your purdue email address'], 400);
    }

    $user = new User;
    $user->firstname = $request->firstname;
    $user->lastname = $request->lastname;
    $user->password = Hash::make($request->password);
    $user->email = $request->email;
    $user->save();

    $user->roles()->attach($baseRole->id);
    $token = $user->getToken();

    return ['message' => 'success', 'token' => $token];
  }

  //Request password reset token
  public function sendPasswordReset(Request $request) {
    $validator = Validator::make($request->all(), [
      'email' => 'required|email|exists:users,email',
    ]);
    if ($validator->fails()) {
      return ['message' => 'error', 'errors' => $validator->errors()->all()];
    }
    $user = User::where('email', $request->email)->first();
    $user->sendPasswordResetEmail();
    return ['message' => 'success'];
  }

  //Confirm password reset
  public function performPasswordReset(Request $request) {
    $validator = Validator::make($request->all(), [
      'token' => 'required',
      'password' => 'required',
    ]);
    if ($validator->fails()) {
      return ['message' => 'validation', 'errors' => $validator->errors()];
    }

    $token = $request->token;
    $password = $request->password;

    $reset = PasswordReset::where('token', $token)->first();
    if(count($reset) < 1) {
      return ['message' => 'invalid_token'];
    }

    if (Carbon::parse($reset->created_at)->addHour(48)->lte(Carbon::now())) {
      return ['message' => 'expired'];
    }

    $user = $reset->User;
    $user->password = Hash::make($password);
    $user->save();

    $reset->delete();

    return ['message' => 'success'];
  }
}
