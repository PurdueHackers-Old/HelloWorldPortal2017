<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Storage;

class Resume extends Model
{
  public function User() {
    return $this->belongsTo('App\Models\User');
  }

  public function Application() {
    return $this->belongsTo('App\Models\Application');
  }

  public function getResumePath() {
    return 'resumes/resume_'.$this->uuid.'.pdf';
  }

/**
 * Returns a presigned url which can be used to view the resume publicly
 * for a short period
 */
  public function getPreSignedUrl() {
    $client = Storage::disk('s3')->getDriver()->getAdapter()->getClient();
    $expiry = "+10 minutes";
    $path = $this->getResumePath();
    $command = $client->getCommand('GetObject', [
        'Bucket' => env('AWS_BUCKET'),
        'Key'    => $path
    ]);
    $request = $client->createPresignedRequest($command, $expiry);
    return (string) $request->getUri();
  }

}
