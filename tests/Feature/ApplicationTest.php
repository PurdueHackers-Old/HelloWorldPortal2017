<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\User;

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


  public function setUp() {
    parent::setUp();
    $this->token = ApplicationTest::getAuthToken();
    $this->user = User::where('email','noreply@purdue.edu')->first();
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
      //Check invalid application
      $appData = ApplicationTest::buildValidApp();
      $appData['major'] = ""; //Invalid major
      $this->actingAs($this->user)
        ->post('api/user/apply',$appData,
        ['Authorization' => 'Bearer '.$this->token])
        ->assertJson(['message' => 'validation']);
      $this->assertDatabaseMissing('applications',['user_id' => $this->user->id]);

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
}
