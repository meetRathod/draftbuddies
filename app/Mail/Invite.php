<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Auth\User;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use Vinkla\Hashids\Facades\Hashids;

class Invite extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $affl_code;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $this->affl_code = Hashids::encode($this->user->id);
        return $this->view('email.invite');
    }
}
