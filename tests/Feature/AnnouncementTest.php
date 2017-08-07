<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\User;
use App\Models\Role;
use App\Models\Announcement;

class AnnouncementTest extends TestCase
{
  use DatabaseTransactions;

  //Mock a sample user and get a token
  public function getAuthToken()
  {
    //Register a sample user
    $user = factory(\App\Models\User::class)->create([
      'firstname' => 'Test',
      'lastname' => 'User',
      'email' => 'noreply@purdue.edu',
    ]);
    $response = $this->call('POST','/api/user/auth',['email' => 'noreply@purdue.edu', 'password' => 'secret']);
    return json_decode($response->getContent(),true)['token'];
  }

  //Mock a sample user and get a token
  public function getAdminAuthToken()
  {
    //Register a sample user
    $user = factory(\App\Models\User::class)->create([
      'firstname' => 'Test',
      'lastname' => 'Admin',
      'email' => 'noreply1@purdue.edu',
    ]);
    // $user->roles()->attach(Role::where('name','admin')->first()->id);
    $response = $this->call('POST','/api/user/auth',['email' => 'noreply1@purdue.edu', 'password' => 'secret']);
    return json_decode($response->getContent(),true)['token'];
  }

  public function setUp() {
    parent::setUp();
    $this->token = AnnouncementTest::getAuthToken();
    $this->user = User::where('email','noreply@purdue.edu')->first();
    $this->adminToken = AnnouncementTest::getAdminAuthToken();
    $this->adminUser = User::where('email','noreply1@purdue.edu')->first();
    $this->adminUser->roles()->attach(Role::where('name','admin')->first()->id);

    $announcement = new Announcement;
    $announcement->message = "Hello Test";
    $announcement->user_id = $this->adminUser->id;
    $announcement->save();

    $this->announcement = $announcement;
  }

  public function testAnnouncementsNonAdmin() {
    $announcementTest = "this is a test announcement";

    //Try posting with a standard auth token
    $this->actingAs($this->user)
      ->post('api/announcements',[
        'message' => $announcementTest
      ],
      ['Authorization' => 'Bearer '.$this->token])
      ->assertStatus(403);

      //Try getting all announcements
      $this->actingAs($this->user)
      ->get('api/announcements')
      ->assertJson(['message' => 'success'])
      ->assertJsonFragment(['message' => $this->announcement->message, 'user_id' => $this->adminUser->id]);

      //Try getting one announcement
      $this->actingAs($this->user)
      ->get('api/announcements/'.$this->announcement->id)
      ->assertJson(['message' => 'success'])
      ->assertJsonFragment(['message' => $this->announcement->message, 'user_id' => $this->adminUser->id]);

      //Try getting one invalid announcement
      $this->actingAs($this->user)
      ->get('api/announcements/0')
      ->assertStatus(404);
  }

  public function testAnnouncementsAdmin() {
    $announcementTest = "this is a test announcement";
    $announcementTestTitle = "Announcement Title";

    //Try posting an invalid auth token
    $this->actingAs($this->adminUser)
      ->post('api/announcements',[
        'message' => $announcementTest,
        'title' => $announcementTestTitle
      ],
      ['Authorization' => 'Bearer '.$this->adminToken])
      ->assertJson(['message' => 'validation'])
      ->assertStatus(400);

    //Try posting with an admin auth token
    $this->actingAs($this->adminUser)
      ->post('api/announcements',[
        'message' => $announcementTest,
        'title' => $announcementTestTitle,
        'should_email' => 'false'
      ],
      ['Authorization' => 'Bearer '.$this->adminToken])
      ->assertJson(['message' => 'success'])
      ->assertStatus(200);
    $this->assertDatabaseHas('announcements',['user_id' => $this->adminUser->id, 'message' => $announcementTest]);
  }

}
