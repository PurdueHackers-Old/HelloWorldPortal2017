<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

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
}
