<?php
use App\Models\{User,ActivityLog};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Hash,Notification,Password};
use Illuminate\Auth\Notifications\ResetPassword;

uses(RefreshDatabase::class);
function accountPayload(array $replace=[]): array {
    return array_replace(['first_name'=>'Ana','middle_name'=>'Cruz','last_name'=>'Santos','username'=>'ana.santos','email'=>'ana@example.test','contact_number'=>'09123456789','role'=>'staff','is_active'=>'1','can_generate_reports'=>'1','password'=>'SecurePass123!','password_confirmation'=>'SecurePass123!'], $replace);
}

it('creates accounts with hashed passwords and audit history and rejects duplicates',function () {
    $admin=User::factory()->create(['role'=>'admin']);
    $this->actingAs($admin)->post('/admin/users',accountPayload())->assertSessionHasNoErrors()->assertRedirect();
    $user=User::where('username','ana.santos')->firstOrFail();
    expect(Hash::check('SecurePass123!',$user->password))->toBeTrue();
    $this->assertDatabaseHas('activity_logs',['user_id'=>$admin->id,'model_id'=>$user->id,'action'=>'Account created']);
    $this->post('/admin/users',accountPayload(['username'=>'ANA.SANTOS','email'=>'other@example.test']))->assertSessionHasErrors('username');
    $this->post('/admin/users',accountPayload(['username'=>'another','email'=>'third@example.test','password_confirmation'=>'wrong']))->assertSessionHasErrors('password');
});

it('blocks staff from all administrator account endpoints and honors report authorization',function () {
    $staff=User::factory()->create(['role'=>'staff','can_generate_reports'=>false]);
    $target=User::factory()->create(['role'=>'staff']);
    $this->actingAs($staff)->get('/admin/users')->assertForbidden();
    $this->post('/admin/users',accountPayload())->assertForbidden();
    $this->put('/admin/users/'.$target->id,accountPayload())->assertForbidden();
    $this->patch('/admin/users/'.$target->id.'/status',['is_active'=>0])->assertForbidden();
    $this->delete('/admin/users/'.$target->id)->assertForbidden();
    $this->post('/admin/users/'.$target->id.'/password-reset')->assertForbidden();
    $this->get('/staff/summary-reports')->assertForbidden();
    $this->get('/account')->assertOk()->assertSee($staff->email);
});

it('deactivates without deletion and revokes sessions across reactivation',function () {
    $admin=User::factory()->create(['role'=>'admin']);
    $staff=User::factory()->create(['role'=>'staff']);
    $log=ActivityLog::create(['user_id'=>$staff->id,'action'=>'Existing history']);
    $this->actingAs($admin)->patch('/admin/users/'.$staff->id.'/status',['is_active'=>0])->assertSessionHasNoErrors();
    expect($staff->fresh()->is_active)->toBeFalse();
    $this->assertDatabaseHas('activity_logs',['id'=>$log->id]);
    $this->assertDatabaseHas('users',['id'=>$staff->id]);
    $this->assertDatabaseHas('activity_logs',['model_id'=>$staff->id,'action'=>'Account deactivated']);
    $this->actingAs($staff->fresh())->withSession(['auth_version'=>0])->get('/staff/staffdashboard')->assertRedirect('/login');
    $this->post('/login',['email'=>$staff->email,'password'=>'password'])->assertSessionHasErrors('email');
    $this->actingAs($admin)->patch('/admin/users/'.$staff->id.'/status',['is_active'=>1])->assertSessionHasNoErrors();
    $this->assertDatabaseHas('activity_logs',['model_id'=>$staff->id,'action'=>'Account activated']);
    $this->actingAs($staff->fresh())->withSession(['auth_version'=>0])->get('/staff/staffdashboard')->assertRedirect('/login');
});

it('protects the last active administrator from status changes demotion and legacy deletion',function () {
    $admin=User::factory()->create(['role'=>'admin']);
    $this->actingAs($admin)->patch('/admin/users/'.$admin->id.'/status',['is_active'=>0])->assertSessionHasErrors('is_active');
    $this->delete('/admin/users/'.$admin->id)->assertSessionHasErrors('is_active');
    $this->put('/admin/users/'.$admin->id,accountPayload(['username'=>'captain','email'=>$admin->email,'role'=>'staff']))->assertSessionHasErrors('role');
    expect($admin->fresh()->role)->toBe('admin');
    expect($admin->fresh()->is_active)->toBeTrue();
});

it('logs account updates preserves a legacy full name and rejects role changes',function () {
    $admin=User::factory()->create(['role'=>'admin']);
    $staff=User::factory()->create(['role'=>'staff','name'=>'Original Compound Full Name']);
    $data=['email'=>$staff->email,'role'=>'admin','is_active'=>1,'can_generate_reports'=>1,'first_name'=>null,'last_name'=>null,'middle_name'=>null,'username'=>null];
    $this->actingAs($admin)->put('/admin/users/'.$staff->id,$data)->assertSessionHasErrors('role');
    $data['role']='staff';
    $this->put('/admin/users/'.$staff->id,$data)->assertSessionHasNoErrors();
    expect($staff->fresh()->name)->toBe('Original Compound Full Name');
    $this->assertDatabaseHas('activity_logs',['model_id'=>$staff->id,'action'=>'Account modified']);
    $this->assertDatabaseMissing('activity_logs',['model_id'=>$staff->id,'action'=>'User role changed']);
    $this->delete('/admin/users/'.$staff->id)->assertSessionHasNoErrors();
    $this->assertDatabaseHas('users',['id'=>$staff->id,'is_active'=>false]);
    $this->assertDatabaseHas('activity_logs',['model_id'=>$staff->id,'action'=>'Account deactivated']);
});

it('supports existing email logins and username logins and records real last login times',function () {
    $staff=User::factory()->create(['role'=>'staff','username'=>'secretary']);
    $this->post('/login',['email'=>'secretary','password'=>'password'])->assertRedirect('/staff/staffdashboard');
    expect($staff->fresh()->last_login_at)->not->toBeNull();
    expect(session('auth_version'))->toBe(0);
    $this->post('/logout')->assertRedirect('/login');
    $this->post('/login',['email'=>$staff->email,'password'=>'password'])->assertRedirect('/staff/staffdashboard');
});

it('sends real broker reset notifications and consumes tokens once while revoking sessions',function () {
    Notification::fake();
    $user=User::factory()->create(['role'=>'staff']);
    $this->post('/forgot-password',['email'=>$user->email])->assertSessionHasNoErrors();
    Notification::assertSentTo($user,ResetPassword::class);
    $token=Password::createToken($user);
    $payload=['token'=>$token,'email'=>$user->email,'password'=>'NewSecure123!','password_confirmation'=>'NewSecure123!'];
    $this->post('/reset-password',$payload)->assertRedirect('/login');
    expect(Hash::check('NewSecure123!',$user->fresh()->password))->toBeTrue();
    expect($user->fresh()->auth_version)->toBe(1);
    $this->post('/reset-password',$payload)->assertSessionHasErrors('email');
    $inactive=User::factory()->create(['role'=>'staff','is_active'=>false]);
    $this->post('/forgot-password',['email'=>$inactive->email]);
    Notification::assertNotSentTo($inactive,ResetPassword::class);
});

it('filters accounts across pagination and shows accurate summaries and only one active sidebar item',function () {
    $admin=User::factory()->create(['role'=>'admin']);
    User::factory()->count(16)->create(['role'=>'staff','is_active'=>false]);
    User::factory()->create(['role'=>'staff','name'=>'Search Target','username'=>'specific.staff','is_active'=>true]);
    $response=$this->actingAs($admin)->get('/admin/users?status=inactive&role=staff')->assertOk()->assertViewHas('inactiveCount',16)->assertViewHas('totalUsers',18)->assertViewHas('activeCount',2);
    $response->assertViewHas('users',fn($users)=>$users->total()===16 && $users->hasMorePages());
    $this->get('/admin/users?search=specific.staff')->assertViewHas('users',fn($users)=>$users->total()===1);
    $this->get('/admin/users?search=Search%20Target')->assertViewHas('users',fn($users)=>$users->total()===1);
    $this->assertSame(1,preg_match_all('/class="nav-item active"/',$response->getContent()));
    if ($directory=getenv('UI_FIXTURE_DIR')) { if(!is_dir($directory))mkdir($directory,0700,true); file_put_contents($directory.'/admin_users.html',$response->getContent()); }
});

it('stores manual positions separately from immutable edit roles without guessing positions', function () {
    $admin=User::factory()->create(['role'=>'admin']);
    $this->actingAs($admin)->post('/admin/users',accountPayload(['position'=>'  Barangay Treasurer  ']))->assertSessionHasNoErrors();
    $user=User::where('username','ana.santos')->firstOrFail();
    expect($user->position)->toBe('Barangay Treasurer')->and($user->role)->toBe('staff')->and($admin->fresh()->position)->toBeNull();
    $this->put('/admin/users/'.$user->id,accountPayload(['position'=>'Records Officer']))->assertSessionHasNoErrors();
    expect($user->fresh()->position)->toBe('Records Officer')->and($user->fresh()->role)->toBe('staff');
    $this->put('/admin/users/'.$user->id,accountPayload(['position'=>'Captain','role'=>'admin']))->assertSessionHasErrors('role');
    expect($user->fresh()->position)->toBe('Records Officer')->and($user->fresh()->role)->toBe('staff');
    $this->put('/admin/users/'.$user->id,accountPayload(['position'=>str_repeat('x',101)]))->assertSessionHasErrors('position');
    $this->put('/admin/users/'.$user->id,accountPayload(['position'=>'   ']))->assertSessionHasNoErrors();
    expect($user->fresh()->position)->toBeNull();
    $this->get('/admin/users')->assertOk()->assertSee('Position / Designation')->assertSee('role_display')->assertDontSee('Staff Member &ndash; Barangay Secretary',false);
    $this->actingAs($user->fresh())->withSession(['auth_version'=>$user->fresh()->auth_version])->patch('/account',['first_name'=>'Ana','last_name'=>'Santos','position'=>'Punong Barangay','role'=>'admin'])->assertSessionHasNoErrors();
    expect($user->fresh()->position)->toBeNull()->and($user->fresh()->role)->toBe('staff');
});
