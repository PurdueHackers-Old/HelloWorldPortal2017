<?php

namespace App\Models;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use JWTAuth;
use Mail;

class User extends Authenticatable
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    public function getToken() {
        $roles = $this->roles;
        $formattedRoles = [];
        foreach($roles as $role) {
          array_push($formattedRoles,$role->name);
        }
        return JWTAuth::fromUser($this, [
          'exp' => strtotime('+1 year'),
          'user_id' => $this->id,
          'email' => $this->email,
          'firstname' => $this->firstname,
          'lastname' => $this->lastname,
          'roles' => $formattedRoles
        ]);
    }

    public function Application() {
      return $this->hasOne('App\Models\Application');
    }

    public function Checkin() {
      return $this->hasOne('App\Models\Checkin');
    }

    public function PasswordReset() {
      return $this->hasOne('App\Models\PasswordReset');
    }

    public function Roles() {
      return $this->belongsToMany('App\Models\Role');
    }

    public function sendPasswordResetEmail() {
        $token = str_random(20);
        $reset = PasswordReset::firstOrNew(['user_id' => $this->id]);
        $reset->token = $token;
        $reset->save();
        $user = $this;

        $targetUrl = getenv('FRONTEND_URL')."/confirmPassword?token=".$token;
        //Send Emails
        Mail::to($user)->queue(new \App\Mail\PasswordReset($token,$targetUrl));
      }

}
