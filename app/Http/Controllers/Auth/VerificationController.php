<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;

class VerificationController extends Controller
{
    public function verify(VerificationToken $token)
	{
	    $token->user()->update([
	        'verified' => true
	    ]);

	    $token->delete();

	    // Uncomment the following lines if you want to login the user 
	    // directly upon email verification
	    // Auth::login($token->user);
	    // return redirect('/home');

	    return redirect('/login')->withInfo('Email verification succesful. Please login again');
	}

    public function resend(Request $request)
    {
    	//
    }
}
