<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PasswordReset extends Model
{
  protected $table = 'passwordreset';
  protected $fillable = ['user_id'];

  public function User() {
    return $this->belongsTo('App\Models\User');
  }
}
