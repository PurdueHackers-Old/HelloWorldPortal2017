<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\PasswordReset;
use App\Models\User;

class UserTest extends TestCase
{
  use DatabaseTransactions;

  public function testRegister()
  {
    //Test registration
    $this->post('/api/user/register',['firstname' => 'Test', 'lastname' => 'user', 'email' => 'noreply@purdue.edu', 'password' => 'password123'])
      ->assertJson(['message' => 'success']);

    $this->assertDatabaseHas('users',['email' => 'noreply@purdue.edu']);

    //Test registration with invalid data
    $this->post('/api/user/register',['firstname' => 'Test', 'email' => 'noreply@purdue.edu', 'password' => 'password123'])
      ->assertJson(['message' => 'validation']);

      //Test registration with non-purdue email
      $this->post('/api/user/register',['firstname' => 'Test', 'lastname' => 'user', 'email' => 'noreply@gmail.com', 'password' => 'password123'])
        ->assertJson(['message' => 'validation']);
  }

  public function testLogin()
  {
    //Test registration
    $this->post('/api/user/register',['firstname' => 'Test', 'lastname' => 'user', 'email' => 'noreply@purdue.edu', 'password' => 'password123'])
      ->assertJson(['message' => 'success']);

    //Try Valid login
    $this->post('/api/user/auth',['email' => 'noreply@purdue.edu', 'password' => 'password123'])
      ->assertJson(['message' => 'success']);

    //Try Invalid Login
    $this->post('/api/user/auth',['email' => 'noreply@purdue.edu', 'password' => 'password1234'])
      ->assertJson(['message' => 'invalid_credentials']);

    //Try Missing Field
    $this->post('/api/user/auth',['email' => 'noreply@pudue.edu'])
      ->assertJson(['message' => 'validation']);
  }

  public function testPasswordReset() {
    //Register a user
    $this->post('/api/user/register',['firstname' => 'Test', 'lastname' => 'user', 'email' => 'noreply@purdue.edu', 'password' => 'password123'])
      ->assertJson(['message' => 'success']);

    //Test password reset request
    $this->post('/api/user/requestPasswordReset',['email' => 'noreply@purdue.edu'])
      ->assertJson(['message' => 'success']);

    $user = User::where('email','noreply@purdue.edu')->first();
    $resetToken = PasswordReset::where('user_id',$user->id)->first()->token;
    $newPassword = "newPassword1";

    //Test password reset confirmation
    $this->post('/api/user/confirmPasswordReset',['token' => $resetToken, 'password' => $newPassword])
      ->assertJson(['message' => 'success']);

    //Try to log in with the new password
    $this->post('/api/user/auth',['email' => 'noreply@purdue.edu', 'password' => $newPassword])
      ->assertJson(['message' => 'success']);  }
}
