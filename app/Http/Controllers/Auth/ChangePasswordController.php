<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Audit\ActivityLogger;
use Illuminate\Support\Facades\Hash;

class ChangePasswordController extends Controller
{
    public function show()
    {
        return view('auth.change-password');
    }

    public function update(ActivityLogger $activity)
    {
        $data = request()->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:12|confirmed',
        ]);
        $user = request()->user();

        if (!Hash::check($data['current_password'], $user->password)) {
            return back()->withErrors(['current_password' => 'The current password is incorrect.']);
        }
        if (Hash::check($data['password'], $user->password)) {
            return back()->withErrors(['password' => 'Choose a password different from the current password.']);
        }

        $user->password = Hash::make($data['password']);
        $user->must_change_password = false;
        $user->save();
        request()->session()->regenerate();

        $activity->log('password_changed', 'user', $user->id, [], ['forced_change_completed' => true], $user->company_id ?? null);

        return redirect()->route('erp.dashboard')->with('success', 'Your password was changed.');
    }
}
