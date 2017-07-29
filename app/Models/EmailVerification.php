<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailVerification extends Model
{
  protected $table = 'emailverification';
  protected $fillable = ['user_id'];

  public function User() {
    return $this->belongsTo('App\Models\User');
  }
}
