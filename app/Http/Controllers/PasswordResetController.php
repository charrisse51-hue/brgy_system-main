<?php
namespace App\Http\Controllers;

use App\Models\{User, ActivityLog};
use App\Support\AccountSessions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Hash, Password};
use Illuminate\Auth\Events\PasswordReset;

class PasswordResetController extends Controller
{
    public function create() { return view('auth.password', ['reset'=>false]); }
    public function edit(Request $request, string $token) { return view('auth.password', ['reset'=>true,'token'=>$token,'email'=>$request->query('email')]); }
    public function send(Request $request)
    {
        $request->validate(['email'=>'required|email|max:255']);
        if (config('mail.default') === 'log') return back()->withErrors(['email'=>'Password reset email delivery is not configured. Please contact the administrator.'])->withInput($request->only('email'));
        try {
            Password::sendResetLink(['email'=>$request->email,'is_active'=>true]);
        } catch (\Throwable $exception) {
            report($exception);
            return back()->withErrors(['email'=>'The mail service is unavailable. Please try again later.'])->withInput($request->only('email'));
        }
        return back()->with('success','If this email belongs to an active account, a password reset link has been sent.');
    }
    public function update(Request $request)
    {
        $data=$request->validate(['token'=>'required|string','email'=>'required|email','password'=>'required|string|min:8|confirmed']);
        $data['is_active']=true;
        $status=Password::reset($data,function (User $user, string $password) {
            DB::transaction(function () use ($user,$password) {
                $user->forceFill(['password'=>Hash::make($password)])->save();
                AccountSessions::revoke($user);
                ActivityLog::create(['user_id'=>$user->id,'action'=>'Password reset completed','description'=>'Account password reset using an email token.','action_type'=>'update','model_type'=>'User','model_id'=>$user->id]);
            });
            event(new PasswordReset($user));
        });
        return $status===Password::PASSWORD_RESET
            ? redirect()->route('login')->with('success',__($status))
            : back()->withErrors(['email'=>__($status)])->withInput($request->only('email'));
    }
}
