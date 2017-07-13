<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RSVP extends Model
{
  protected $table = 'rsvps';

  public function User() {
    return $this->belongsTo('App\Models\User');
  }

  public function Application() {
    return $this->belongsTo('App\Models\Application');
  }

}
