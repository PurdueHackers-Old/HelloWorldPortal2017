<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class AnnouncementMail extends Mailable
{
    use Queueable, SerializesModels;
    public $messageBody;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($messageBody)
    {
      $this->messageBody = $messageBody;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
      return $this->from('noreply@purduehackers.com', 'Purdue Hackers')
        ->subject('Hello World Update')
        ->view('emails.announcement');
      }
}
