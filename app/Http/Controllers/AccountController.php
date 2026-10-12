<?php

namespace App\Http\Controllers;

use App\Models\{User, ActivityLog};
use App\Support\AccountSessions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Hash};
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AccountController extends Controller
{
    public function show(Request $request)
    {
        return view(in_array($request->user()->role, ['admin', 'staff'], true) ? 'account' : 'account_legacy', ['user' => $request->user()]);
    }

    public function update(Request $request)
    {
        $user = $request->user();
        abort_unless(in_array($user->role, ['admin', 'staff'], true), 403);
        $data = $request->validateWithBag('profileUpdate', [
            'first_name' => [$user->first_name ? 'required' : 'nullable', 'required_with:middle_name,last_name', 'string', 'max:100'],
            'middle_name' => 'nullable|string|max:100',
            'last_name' => [$user->last_name ? 'required' : 'nullable', 'required_with:first_name,middle_name', 'string', 'max:100'],
            'contact_number' => ['nullable', 'string', 'max:20', 'regex:/^[+0-9()\s-]{7,20}$/'],
        ]);
        DB::transaction(function () use ($user, $data) {
            $user = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $parts = array_filter([$data['first_name'] ?? '', $data['middle_name'] ?? '', $data['last_name'] ?? ''], fn ($part) => trim($part) !== '');
            if ($parts) $data['name'] = implode(' ', array_map('trim', $parts));
            $user->update($data);
            ActivityLog::create(['user_id' => $user->id, 'action' => 'Profile Updated', 'description' => 'Successful: account holder updated permitted personal information.', 'action_type' => 'update', 'model_type' => 'User', 'model_id' => $user->id]);
        });
        return redirect()->route('account.show')->with('profile_success', 'Your personal information has been updated successfully.');
    }

    public function password(Request $request)
    {
        $user = $request->user();
        abort_unless(in_array($user->role, ['admin', 'staff'], true), 403);
        $data = $request->validateWithBag('passwordUpdate', [
            'current_password' => ['bail', 'required', 'string', 'current_password:web'],
            'password' => ['bail', 'required', 'string', 'confirmed', Password::min(12)->uncompromised(), function ($attribute, $value, $fail) use ($user) {
                if (trim($value) === '') $fail('Choose a password containing more than spaces.');
                if (Hash::check($value, $user->password)) $fail('Choose a new password different from your current password.');
                if (in_array(strtolower(trim($value)), ['password1234', 'password12345', 'password123456', '123456789012', 'qwerty12345678', 'administrator', 'letmein123456'], true)) $fail('Choose a less commonly used password or a strong passphrase.');
            }],
            'password_confirmation' => ['required', 'string'],
        ], [
            'current_password.current_password' => 'Your current password is incorrect.',
            'password.confirmed' => 'The new password and confirmation do not match.',
        ]);
        DB::transaction(function () use ($user, $data) {
            $user = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            if (!Hash::check($data['current_password'], $user->password)) {
                throw ValidationException::withMessages(['current_password' => 'Your current password has changed. Please try again.'])->errorBag('passwordUpdate');
            }
            $user->password = Hash::make($data['password']);
            AccountSessions::revoke($user);
            ActivityLog::create(['user_id' => $user->id, 'action' => 'Password Changed', 'description' => 'Successful: account holder changed their own password. Other sessions revoked.', 'action_type' => 'update', 'model_type' => 'User', 'model_id' => $user->id]);
        });
        $user->refresh();
        // Recreate only this authenticated session after revoking all earlier sessions.
        $request->session()->regenerate(true);
        $request->session()->regenerateToken();
        $request->session()->put('auth_version', (int) $user->auth_version);
        $request->session()->put('password_hash_web', $user->password);
        return redirect()->to(route('account.show').'#change-password')->with('password_success', 'Your password has been changed successfully.');
    }
}
