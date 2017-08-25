<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\User;
use App\Models\Role;
use App\Models\Application;

class ApplicationTest extends TestCase
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
    $this->token = ApplicationTest::getAuthToken();
    $this->user = User::where('email','noreply@purdue.edu')->first();
    $this->adminToken = ApplicationTest::getAdminAuthToken();
    $this->adminUser = User::where('email','noreply1@purdue.edu')->first();
    $this->adminUser->roles()->attach(Role::where('name','admin')->first()->id);
    $this->adminUser->roles()->attach(Role::where('name','devteam')->first()->id);
  }

  public function buildValidApp() {
    //Create a sample valid application
    $appData = [
      'class_year' => 'freshman',
      'grad_year' => '2021',
      'major' => 'Computer Science',
      'referral' => 'none',
      'hackathon_count' => 0,
      'shirt_size' => 'l',
      'dietary_restrictions' => 'l',
      'website' => 'http://github.com',
      'longanswer_1' => 'Sample question 1',
      'longanswer_2' => 'Sample question 2',
    ];
    return $appData;
  }

  public function testSubmitApplication() {
    $appData = ApplicationTest::buildValidApp();
    $appData['major'] = ""; //Invalid major

    //Check application with unverified email
    $this->actingAs($this->user)
    ->post('api/user/apply',$appData,
    ['Authorization' => 'Bearer '.$this->token])
    ->assertJson(['message' => 'unverified_email']);

    //Check verification route
    $this->actingAs($this->user)
    ->get('api/user/isverified',$appData,
    ['Authorization' => 'Bearer '.$this->token])
    ->assertJson(['message' => 'success', 'verified' => 0]);

    //Verify user email
    $this->user->verified = true;
    $this->user->save();

    //Check verification route
    $this->actingAs($this->user)
    ->get('api/user/isverified',$appData,
    ['Authorization' => 'Bearer '.$this->token])
    ->assertJson(['message' => 'success', 'verified' => 1]);

    //Check invalid application
    $this->actingAs($this->user)
    ->post('api/user/apply',$appData,
    ['Authorization' => 'Bearer '.$this->token])
    ->assertJson(['message' => 'validation']);
    $this->assertDatabaseMissing('applications',['user_id' => $this->user->id]);

    //Check that application fails if they're closed
    putenv("APPLICATION_MODE=CLOSED");
    $appData = ApplicationTest::buildValidApp();
    $this->actingAs($this->user)
    ->post('api/user/apply',$appData,
    ['Authorization' => 'Bearer '.$this->token])
    ->assertJsonFragment(['message' => 'validation','application_mode' => ['Applications are not open']]);
    $this->assertDatabaseMissing('applications',['user_id' => $this->user->id]);
    putenv("APPLICATION_MODE=OPEN");


    //Check valid application
    $appData = ApplicationTest::buildValidApp();
    $this->actingAs($this->user)
    ->post('api/user/apply',$appData,
    ['Authorization' => 'Bearer '.$this->token])
    ->assertJson(['message' => 'success']);
    $this->assertDatabaseHas('applications',['user_id' => $this->user->id]);

    //Check re-application fails
    $appData = ApplicationTest::buildValidApp();
    $this->actingAs($this->user)
    ->post('api/user/apply',$appData,
    ['Authorization' => 'Bearer '.$this->token])
    ->assertJson(['message' => 'application_already_exists']);

    //Check viewing your own application later
    $this->actingAs($this->user)
    ->get('api/user/application',['HTTP_Authorization' => 'Bearer '.$this->token])
    ->assertJsonFragment($appData);

    //Check viewing all applications fails
    $this->actingAs($this->user)
    ->get('api/applications',['HTTP_Authorization' => 'Bearer '.$this->token])
    ->assertStatus(403);

    //Check viewing all applications fails
    $this->actingAs($this->user)
    ->get('api/applications/1',['HTTP_Authorization' => 'Bearer '.$this->token])
    ->assertStatus(403);
  }

  public function testUpdateApplication() {
    //Verify user email
    $this->user->verified = true;
    $this->user->save();

    //Create valid application
    $appData = ApplicationTest::buildValidApp();
    $this->actingAs($this->user)
    ->post('api/user/apply',$appData,
    ['Authorization' => 'Bearer '.$this->token])
    ->assertJson(['message' => 'success']);
    $this->assertDatabaseHas('applications',['user_id' => $this->user->id]);

    //Update the application
    $newAnswerText = "Actually I changed my mind";
    $appData['longanswer_1'] = $newAnswerText;
    $this->actingAs($this->user)
    ->post('api/user/updateApplication',$appData,
    ['Authorization' => 'Bearer '.$this->token])
    ->assertJsonFragment(['longanswer_1' => $newAnswerText]);
    $this->assertDatabaseHas('applications',['user_id' => $this->user->id, 'longanswer_1' => $newAnswerText]);
  }

  public function testExecApplicationActions() {

    //Verify user email
    $this->adminUser->verified = true;
    $this->adminUser->save();

    //Create valid application
    $appData = ApplicationTest::buildValidApp();
    $this->actingAs($this->adminUser)
    ->post('api/user/apply',$appData,
    ['Authorization' => 'Bearer '.$this->adminToken])
    ->assertJson(['message' => 'success']);
    $this->assertDatabaseHas('applications',['user_id' => $this->adminUser->id]);

    //Check viewing all applications works
    $this->actingAs($this->adminUser)
    ->get('api/applications',['HTTP_Authorization' => 'Bearer '.$this->adminToken])
    ->assertJsonFragment(['user_id' => $this->adminUser->id])
    ->assertStatus(200);

    $newApp = Application::where('user_id',$this->adminUser->id)->first();


    //Check viewing a specific applications works
    $this->actingAs($this->adminUser)
    ->get('api/applications/'.$newApp->id,['HTTP_Authorization' => 'Bearer '.$this->adminToken])
    ->assertJsonFragment(['user_id' => $this->adminUser->id])
    ->assertStatus(200);

    //Check viewing an invalid applications 404's
    $this->actingAs($this->adminUser)
    ->get('api/applications/0',['HTTP_Authorization' => 'Bearer '.$this->adminToken])
    ->assertStatus(404);
  }

  public function testExecApplicationAcceptances() {
    //Verify user email
    $this->adminUser->verified = true;
    $this->adminUser->save();
    //Create valid application
    $appData = ApplicationTest::buildValidApp();
    $this->actingAs($this->adminUser)
    ->post('api/user/apply',$appData,
    ['Authorization' => 'Bearer '.$this->adminToken])
    ->assertJson(['message' => 'success']);
    $this->assertDatabaseHas('applications',['user_id' => $this->adminUser->id]);

    $newApp = Application::where('user_id',$this->adminUser->id)->first();

    //Update app status
    $this->actingAs($this->user)
    ->post('api/applications/'.$newApp->id.'/setStatus',['status' => 'accepted'],
    ['Authorization' => 'Bearer '.$this->adminToken])
    ->assertJsonFragment(['message' => 'success']);

    //Check that only the internal status is updated
    $this->actingAs($this->adminUser)
    ->get('api/applications/'.$newApp->id,['HTTP_Authorization' => 'Bearer '.$this->adminToken])
    ->assertJsonFragment(['user_id' => $this->adminUser->id, 'status_internal' => 'accepted', 'status_public' => 'pending'])
    ->assertStatus(200);

    //Publish results
    $this->actingAs($this->user)
    ->post('api/exec/publishStatus',[],
    ['Authorization' => 'Bearer '.$this->adminToken])
    ->assertJsonFragment(['message' => 'success']);

    //Check that only the public status is now updated
    $this->actingAs($this->adminUser)
    ->get('api/applications/'.$newApp->id,['HTTP_Authorization' => 'Bearer '.$this->adminToken])
    ->assertJsonFragment(['user_id' => $this->adminUser->id, 'status_internal' => 'accepted', 'status_public' => 'accepted'])
    ->assertStatus(200);

    //Update app status to be un-accepted
    $this->actingAs($this->user)
    ->post('api/applications/'.$newApp->id.'/setStatus',['status' => 'rejected'],
    ['Authorization' => 'Bearer '.$this->adminToken])
    ->assertJsonFragment(['message' => 'success']);

    //Publish results and check for the failure
    $this->actingAs($this->user)
    ->post('api/exec/publishStatus',[],
    ['Authorization' => 'Bearer '.$this->adminToken])
    ->assertStatus(400);
    //Make sure database is still accurate
    $this->assertDatabaseHas('applications',['user_id' => $this->adminUser->id, 'status_public' => 'accepted', 'status_internal' => 'rejected']);

  }

  public function testVerifyEmail() {
    //User should be unverified
    $this->assertDatabaseHas('users',['email' => 'noreply@purdue.edu', 'verified' => false]);

    //Request a email verification
    $this->actingAs($this->user)
    ->post('api/user/resendVerificationEmail',[],
    ['Authorization' => 'Bearer '.$this->token])
    ->assertJson(['message' => 'success']);
    $this->assertDatabaseHas('emailverification',['user_id' => $this->user->id]);

    //Try to verify email with a bad token
    $this->actingAs($this->user)
    ->post('api/user/confirmEmail',['token' => 'invalid_token'],
    ['Authorization' => 'Bearer '.$this->token])
    ->assertJson(['message' => 'invalid_token']);

    $newToken = \App\Models\EmailVerification::where('user_id',$this->user->id)->first();

    //Try to verify email with a good token
    $this->actingAs($this->user)
    ->post('api/user/confirmEmail',['token' => $newToken->token],
    ['Authorization' => 'Bearer '.$this->token])
    ->assertJson(['message' => 'success']);

    //Make sure the email verification has been deleted
    $this->assertDatabaseMissing('emailverification',['user_id' => $this->user->id]);
    //User should now be unverified
    $this->assertDatabaseHas('users',['email' => 'noreply@purdue.edu', 'verified' => true]);

    //User should not be able to re-verify
    $this->actingAs($this->user)
    ->post('api/user/resendVerificationEmail',[],
    ['Authorization' => 'Bearer '.$this->token])
    ->assertStatus(400);
  }

  public function testCheckin() {
    //Verify user email
    $this->adminUser->verified = true;
    $this->adminUser->save();

    //Create valid application
    $appData = ApplicationTest::buildValidApp();
    $this->actingAs($this->adminUser)
    ->post('api/user/apply',$appData,
    ['Authorization' => 'Bearer '.$this->adminToken])
    ->assertJson(['message' => 'success']);
    $this->assertDatabaseHas('applications',['user_id' => $this->adminUser->id]);

    //Check that checkin on an unaccepted user fails
    $this->actingAs($this->adminUser)
    ->post('api/exec/checkin',['email' => "noreply1@purdue.edu"],
    ['Authorization' => 'Bearer '.$this->adminToken])
    ->assertJson(['message' => 'validation'])
    ->assertStatus(403);
    $this->assertDatabaseMissing('checkins',['user_id' => $this->adminUser->id]);

    //Try to search with missing params
    $this->post('/api/user/search',[],['HTTP_Authorization' => 'Bearer '.$this->adminToken])
      ->assertStatus(400);

    //Test that searching for the user doesnt show this user
    $this->actingAs($this->adminUser)
    ->post('api/user/search',['searchvalue' => 'noreply1'],
    ['Authorization' => 'Bearer '.$this->adminToken])
    ->assertJsonMissing(['user_id' => $this->adminUser->id]);

    //Accept the user
    $this->adminUser->application->status_public = "accepted";
    $this->adminUser->application->save();
    $this->assertDatabaseHas('applications',['user_id' => $this->adminUser->id, 'status_public' => 'accepted']);

    //Test that searching for the user works now that they're accpeted
    $this->actingAs($this->adminUser)
    ->post('api/user/search',['searchvalue' => 'noreply1'],
    ['Authorization' => 'Bearer '.$this->adminToken])
    ->assertJsonFragment(['user_id' => $this->adminUser->id]);


    //Test the checkin again now that the user has been accepted
    $this->actingAs($this->adminUser)
    ->post('api/exec/checkin',['email' => "noreply1@purdue.edu"],
    ['Authorization' => 'Bearer '.$this->adminToken])
    ->assertJson(['message' => 'success'])
    ->assertStatus(200);
    $this->assertDatabaseHas('checkins',['user_id' => $this->adminUser->id]);

    //Test that searching for the user doesnt show this user now that they're checked in
    $this->actingAs($this->adminUser)
    ->post('api/user/search',['searchvalue' => 'noreply1'],
    ['Authorization' => 'Bearer '.$this->adminToken])
    ->assertJsonMissing(['user_id' => $this->adminUser->id]);
  }
}
