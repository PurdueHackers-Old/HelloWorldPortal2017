<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
use App\Models\User;
use Hash;
use Auth;
use App\Models\Role;
use App\Models\PasswordReset;
use App\Models\EmailVerification;
use App\Models\InterestSignup;
use Carbon\Carbon;
use Mail;

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
      return response()->json(['message' => 'validation', 'errors' => $validator->errors()], 400);
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
      return response()->json(['message' => 'validation', 'errors' => $validator->errors()], 400);
    }

    $baseRole = Role::where('name','user')->first();
    if(count($baseRole) == 0) {
      return response()->json(['message' => 'internal_role_error'], 500);
    }

    //Require people to register with a purdue email
    if(strpos($request->email,"@purdue.edu") === false) {
      $validator->getMessageBag()->add('email', 'Email must be a purdue email address');
      return response()->json(['message' => 'validation', 'errors' => $validator->errors()], 400);
    }

    $user = new User;
    $user->firstname = $request->firstname;
    $user->lastname = $request->lastname;
    $user->password = Hash::make($request->password);
    $user->email = $request->email;
    $user->verified = false;
    $user->save();

    $user->roles()->attach($baseRole->id);
    $token = $user->getToken();

    $user->sendEmailVerificationEmail(); //Ask user to verify email
    return ['message' => 'success', 'token' => $token];
  }

  //Request password reset token
  public function sendPasswordReset(Request $request) {
    $validator = Validator::make($request->all(), [
      'email' => 'required|email|exists:users,email',
    ]);
    if ($validator->fails()) {
      return response()->json(['message' => 'validation', 'errors' => $validator->errors()], 400);
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
      return response()->json(['message' => 'validation', 'errors' => $validator->errors()], 400);
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

  //Ask to have the verification email sent again
  public function requestVerificationEmail(Request $request) {
    $user = Auth::user();
    if($user->verified) {
      return response()->json(['message' => 'Already verified email'], 400);
    }
    $user->sendEmailVerificationEmail();
    return ['message' => 'success'];
  }

  //Confirm email
  public function confirmVerificationEmail(Request $request) {
    $validator = Validator::make($request->all(), [
      'token' => 'required',
    ]);
    if ($validator->fails()) {
      return response()->json(['message' => 'validation', 'errors' => $validator->errors()], 400);
    }
    ;
    $password = $request->password;

    $verification = EmailVerification::where('token', $request->token)
      ->where('user_id',Auth::id())->first();
    if(count($verification) < 1) {
      return ['message' => 'invalid_token'];
    }

    $user = $verification->User;
    $user->verified = true;
    $user->save();

    $verification->delete();

    return ['message' => 'success'];
  }

  public function subscribeToInterest(Request $request) {
    $validator = Validator::make($request->all(), [
      'email' => 'required|email',
    ]);
    if ($validator->fails()) {
      return response()->json(['message' => 'validation', 'errors' => $validator->errors()], 400);
    }

    //Save this email for later
    $signup = InterestSignup::where(['email' => $request->email])->first();
    if(count($signup) >= 1) {
      //They already signed up
      return response()->json(['message' => 'success']);
    }
    $signup = new InterestSignup;
    $signup->email = $request->email;
    $signup->save();

    // Send an email right now to confirm
    Mail::to($signup->email)->queue(new \App\Mail\InterestMail());
    return response()->json(['message' => 'success']);
  }
}
