<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class WaitlistedMail extends Mailable
{
    public $application;
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
     public function __construct($application)
     {
       $this->application = $application;
     }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
      return $this->from('noreply@purduehackers.com', 'Purdue Hackers')
        ->subject('Hello World')
        ->view('emails.waitlisted');
      }
}
