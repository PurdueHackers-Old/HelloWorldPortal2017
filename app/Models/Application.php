<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Application extends Model
{
  protected $fillable = ['sampleQuestion'];

    public function User() {
      return $this->belongsTo('App\Models\User');
    }
}
