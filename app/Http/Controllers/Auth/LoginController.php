<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers {
        sendFailedLoginResponse as protected traitSendFailedLoginResponse;
    }

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    protected function credentials(Request $request)
    {
        return array_merge($request->only($this->username(), 'password'), [
            'is_active' => true,
        ]);
    }

    protected function authenticated(Request $request, $user)
    {
        DB::table('users')->where('id', $user->id)->update([
            'last_login_at' => now(),
            'updated_at' => now(),
        ]);

        $this->recordLoginAttempt($request, $user->id, true);
    }

    protected function sendFailedLoginResponse(Request $request)
    {
        $userId = DB::table('users')->where('email', $request->input($this->username()))->value('id');
        $this->recordLoginAttempt($request, $userId, false);

        return $this->traitSendFailedLoginResponse($request);
    }

    protected function loggedOut(Request $request)
    {
        return redirect()->route('login');
    }

    private function recordLoginAttempt(Request $request, $userId, $wasSuccessful)
    {
        if (!Schema::hasTable('login_logs')) {
            return;
        }

        DB::table('login_logs')->insert([
            'user_id' => $userId,
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
            'was_successful' => $wasSuccessful,
            'logged_in_at' => $wasSuccessful ? now() : null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
