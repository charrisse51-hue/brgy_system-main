<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{User, ActivityLog};
use App\Support\AccountSessions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Hash, Password};
use Illuminate\Validation\{Rule, ValidationException};

class UserController extends Controller
{
    public function __construct() { $this->middleware(['auth', 'role:admin']); }

    public function index(Request $request)
    {
        $filters = $request->validate(['search'=>'nullable|string|max:150', 'role'=>'nullable|in:admin,staff', 'status'=>'nullable|in:active,inactive']);
        $base = User::whereIn('role', ['admin','staff']);
        $query = clone $base;
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(fn ($q) => $q->where('name','like',"%{$search}%")->orWhere('username','like',"%{$search}%")->orWhere('email','like',"%{$search}%"));
        }
        if (!empty($filters['role'])) $query->where('role',$filters['role']);
        if (!empty($filters['status'])) $query->where('is_active',$filters['status']==='active');
        $users = $query->latest()->orderByDesc('id')->paginate(15)->withQueryString();
        $totalUsers = (clone $base)->count();
        $activeCount = (clone $base)->where('is_active',true)->count();
        $inactiveCount = (clone $base)->where('is_active',false)->count();
        return view('admin.user_management',compact('users','totalUsers','activeCount','inactiveCount'));
    }

    private function data(Request $request, ?User $user = null): array
    {
        if ($user) {
            if ($request->has('role') && $request->input('role') !== $user->role) {
                throw ValidationException::withMessages(['role'=>'The assigned system role cannot be changed when editing an account.']);
            }
            $request->merge(['role'=>$user->role]);
        }
        if (is_string($request->input('position'))) {
            $request->merge(['position'=>trim($request->input('position')) ?: null]);
        }
        if ($request->filled('username')) $request->merge(['username'=>strtolower(trim($request->username))]);
        $data = $request->validate([
            'first_name'=>[($user && !$user->first_name) ? 'nullable' : 'required','string','max:100'],
            'middle_name'=>'nullable|string|max:100',
            'last_name'=>[($user && !$user->last_name) ? 'nullable' : 'required','string','max:100'],
            'username'=>[($user && !$user->username) ? 'nullable' : 'required','string','min:3','max:50','regex:/^[a-z0-9_.-]+$/',Rule::unique('users','username')->ignore($user?->id)],
            'email'=>['required','email','max:255',Rule::unique('users','email')->ignore($user?->id)],
            'contact_number'=>['nullable','string','max:20','regex:/^[+0-9()\s-]{7,20}$/'],
            'position'=>'nullable|string|max:100',
            'role'=>'required|in:admin,staff', 'is_active'=>'required|boolean',
            'can_generate_reports'=>'required|boolean',
            'password'=>[$user ? 'nullable' : 'required','string','min:8','confirmed'],
        ]);
        $parts = array_filter([$data['first_name'] ?? '',$data['middle_name'] ?? '',$data['last_name'] ?? ''],fn ($part) => trim($part)!=='');
        $data['name'] = $parts ? implode(' ',array_map('trim',$parts)) : $user->name;
        if (empty($data['password'])) unset($data['password']);
        else $data['password'] = Hash::make($data['password']);
        return $data;
    }

    private function log(User $user, string $action, string $description): void
    {
        ActivityLog::create(['user_id'=>auth()->id(),'action'=>$action,'description'=>$description,'action_type'=>'update','model_type'=>'User','model_id'=>$user->id]);
    }

    private function protectAdministrator(User $user, array $data): void
    {
        // All account mutations serialize on active admin rows to prevent concurrent removal of the last admin.
        if ($user->role==='admin' && $user->is_active && (!(bool)$data['is_active'] || ($data['role'] ?? $user->role)!=='admin')) {
            if (User::where('role','admin')->where('is_active',true)->count() <= 1) {
                throw ValidationException::withMessages(['is_active'=>'The last active administrator cannot be deactivated or demoted.']);
            }
        }
    }

    public function store(Request $request)
    {
        $data = $this->data($request);
        $user = DB::transaction(function () use ($data) {
            $user = User::create($data);
            $this->log($user,'Account created','Created '.$user->role.' account for '.$user->name.'.');
            return $user;
        });
        return redirect()->route('admin.users.index',['new_id'=>$user->id])->with('success','Account created successfully.');
    }

    public function update(Request $request, User $user)
    {
        abort_unless(in_array($user->role,['admin','staff'],true),404);
        $data = $this->data($request,$user);
        DB::transaction(function () use ($user,$data) {
            User::where('role','admin')->where('is_active',true)->orderBy('id')->lockForUpdate()->get();
            $user->refresh();
            $this->protectAdministrator($user,$data);
            $oldRole=$user->role; $oldActive=$user->is_active;
            $revoke=(!$data['is_active']) || $oldRole!==$data['role'] || isset($data['password']);
            $user->update($data);
            $this->log($user,'Account modified','Updated account for '.$user->name.'.');
            if ($oldRole!==$user->role) $this->log($user,'User role changed',"Role changed from {$oldRole} to {$user->role}.");
            if ($oldActive!==$user->is_active) $this->log($user,$user->is_active ? 'Account activated' : 'Account deactivated','Account for '.$user->name.'.');
            if ($revoke) AccountSessions::revoke($user);
        });
        return redirect()->route('admin.users.index')->with('success','Account updated successfully.');
    }

    public function status(Request $request, User $user)
    {
        $data=$request->validate(['is_active'=>'required|boolean']);
        return $this->setStatus($user,(bool)$data['is_active']);
    }

    private function setStatus(User $user, bool $active)
    {
        abort_unless(in_array($user->role,['admin','staff'],true),404);
        DB::transaction(function () use ($user,$active) {
            User::where('role','admin')->where('is_active',true)->orderBy('id')->lockForUpdate()->get();
            $user->refresh();
            $this->protectAdministrator($user,['is_active'=>$active]);
            if ($user->is_active===$active) return;
            $user->update(['is_active'=>$active]);
            AccountSessions::revoke($user);
            $this->log($user,$active ? 'Account activated' : 'Account deactivated','Account for '.$user->name.'.');
        });
        return back()->with('success',$active ? 'Account activated.' : 'Account deactivated. History has been preserved.');
    }

    // Retain the existing endpoint, replacing permanent deletion with deactivation.
    public function destroy(User $user) { return $this->setStatus($user,false); }

    public function resetPassword(User $user)
    {
        abort_unless(in_array($user->role,['admin','staff'],true),404);
        if (config('mail.default') === 'log') return back()->with('error','Email delivery is not configured. Configure the mail service before sending password reset links.');
        if (!$user->is_active) return back()->with('error','Activate the account before sending a reset link.');
        try {
            $status=Password::sendResetLink(['email'=>$user->email,'is_active'=>true]);
        } catch (\Throwable $exception) {
            report($exception);
            return back()->with('error','The reset email could not be sent. Please check the mail service and try again.');
        }
        if ($status===Password::RESET_LINK_SENT) $this->log($user,'Password reset requested','Reset link requested for '.$user->name.'.');
        return back()->with($status===Password::RESET_LINK_SENT ? 'success' : 'error',__($status));
    }
}
