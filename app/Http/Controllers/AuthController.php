<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
use App\Models\User;
use Hash;
use Auth;
use App\Models\Role;

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
    $field = filter_var($email, FILTER_VALIDATE_EMAIL) ? 'email' : 'name';
    if (Auth::attempt([$field => $email, 'password' => $request->password])) {
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
}
