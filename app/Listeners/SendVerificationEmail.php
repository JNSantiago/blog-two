<?php
namespace App\Listeners;
use Mail;
use App\User;
use App\Mail\SendVerificationToken;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
class SendVerificationEmail
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }
    /**
     * Handle the event.
     *
     * @param  Event  $event
     * @return void
     */
    public function handle($event)
    {
        //$user = User::find($event->user->id);
        //dd($user->verificationToken);
        Mail::to($event->user)->send(new SendVerificationToken($event->user->verificationToken));
    }
}