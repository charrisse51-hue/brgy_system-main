<?php
namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AccountSessions
{
    public static function revoke(User $user): void
    {
        $user->auth_version = (int) $user->auth_version + 1;
        $user->remember_token = Str::random(60);
        $user->save();
        if (config('session.driver') === 'database') {
            DB::connection(config('session.connection'))->table(config('session.table', 'sessions'))->where('user_id', $user->id)->delete();
        }
    }
}
