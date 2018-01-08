<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use App\User;
use App\Events\UserRequestedVerificationEmail;

use App\Http\Controllers\Controller;
use App\VerificationToken;

class VerificationController extends Controller
{
    public function verify(VerificationToken $token)
	{
	    $user = User::find($token->user_id);
    	$user->verified = true;
    	$user->save();

	    $token->delete();

	    // Uncomment the following lines if you want to login the user 
	    // directly upon email verification
	    // Auth::login($token->user);
	    // return redirect('/home');

	    return redirect('/login')->withInfo('Email verification succesful. Please login again');
	}

    public function resend(Request $request)
	{
	    $user = User::byEmail($request->email)->firstOrFail();

	    if($user->hasVerifiedEmail()) {
	        return redirect('/home');
	    }

	    event(new UserRequestedVerificationEmail($user));

	    return redirect('/login')->withInfo('Verification email resent. Please check your inbox');
	}
}
