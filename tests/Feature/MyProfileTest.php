<?php

use App\Models\{User, ActivityLog};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Hash, Http, Auth, DB};

uses(RefreshDatabase::class);

beforeEach(function () { Http::fake(['api.pwnedpasswords.com/*' => fn () => Http::response(config('testing.pwned_body', ''), 200)]); });

it('shows only the authenticated profile and one account menu', function (string $role) {
    $user = User::factory()->create(['role'=>$role,'first_name'=>'Account','last_name'=>'Holder','username'=>'account.holder']);
    $response = $this->actingAs($user)->get('/account')->assertOk()->assertSee('My Profile')->assertSee($user->email)->assertSee('Change Password')->assertDontSee('name="user_id"',false)
        ->assertDontSee('href="'.route('account.show').'#change-password"', false)
        ->assertSee('data-lucide="log-out"', false)->assertSee('id="changePasswordForm"', false);
    expect(substr_count($response->getContent(), '<details class="account-menu">'))->toBe(1);
    if ($dir = getenv('UI_FIXTURE_DIR')) {
        if (!is_dir($dir)) mkdir($dir,0777,true);
        file_put_contents($dir.'/profile-'.$role.'.html',$this->get('/account')->getContent());
    }
})->with(['admin','staff']);

it('requires authentication for viewing and updating profiles', function () {
    $this->get('/account')->assertRedirect('/login');
    $this->patch('/account')->assertRedirect('/login');
    $this->put('/account/password')->assertRedirect('/login');
});

it('updates only permitted personal details and preserves identity and permissions', function () {
    $staff=User::factory()->create(['role'=>'staff','first_name'=>'Old','last_name'=>'Name','username'=>'staff.member']);
    $admin=User::factory()->create(['role'=>'admin']);
    $hash=$staff->password;
    $this->actingAs($staff)->patch('/account', ['first_name'=>'Elvira','middle_name'=>null,'last_name'=>'Arceo','contact_number'=>'09123456789','user_id'=>$admin->id,'role'=>'admin','username'=>'changed','email'=>'changed@example.com','is_active'=>false,'can_generate_reports'=>false,'password'=>'ignored'])
        ->assertRedirect(route('account.show'))->assertSessionHas('profile_success');
    $staff->refresh();
    expect($staff->name)->toBe('Elvira Arceo')->and($staff->role)->toBe('staff')->and($staff->username)->toBe('staff.member')->and($staff->password)->toBe($hash)->and($staff->is_active)->toBeTrue();
    expect(ActivityLog::where('action','Profile Updated')->where('user_id',$staff->id)->exists())->toBeTrue();
});

it('preserves legacy names and validates profile contact and name components', function () {
    $user=User::factory()->create(['role'=>'admin','name'=>'Original Compound Full Name']);
    $this->actingAs($user)->patch('/account',['first_name'=>null,'middle_name'=>null,'last_name'=>null,'contact_number'=>null])->assertSessionHasNoErrors();
    expect($user->fresh()->name)->toBe('Original Compound Full Name');
    $this->patch('/account',['middle_name'=>'Only middle','contact_number'=>'invalid'])->assertSessionHasErrors(['first_name','last_name','contact_number'],null,'profileUpdate');
});

it('changes own password securely and preserves only the current session', function (string $role) {
    $old='Current correct passphrase 42!'; $new='A brand new private passphrase 77!';
    $user=User::factory()->create(['role'=>$role,'password'=>Hash::make($old)]);
    $other=User::factory()->create(['role'=>'admin']); $otherHash=$other->password;
    $this->actingAs($user)->withSession(['auth_version'=>0])->put('/account/password',['current_password'=>$old,'password'=>$new,'password_confirmation'=>$new,'user_id'=>$other->id])
        ->assertRedirect(route('account.show').'#change-password')->assertSessionHas('password_success')->assertSessionHas('auth_version',1);
    $user->refresh();
    expect(Hash::check($new,$user->password))->toBeTrue()->and(Hash::check($old,$user->password))->toBeFalse()->and($other->fresh()->password)->toBe($otherHash);
    $this->get('/account')->assertOk();
    $log=ActivityLog::where('action','Password Changed')->firstOrFail();
    expect($log->user_id)->toBe($user->id)->and($log->description)->toContain('Successful')->not->toContain($new)->not->toContain($old);
    $this->withSession(['auth_version'=>0])->get('/account')->assertRedirect('/login');
    $this->post('/login',['email'=>$user->email,'password'=>$old])->assertSessionHasErrors('email');
    $this->post('/login',['email'=>$user->email,'password'=>$new])->assertRedirect($role==='admin'?'/admin/dashboard':'/staff/staffdashboard');
})->with(['admin','staff']);

it('rejects wrong current empty short unchanged mismatched and compromised passwords', function () {
    $old='Current correct passphrase 42!';
    $user=User::factory()->create(['role'=>'staff','password'=>Hash::make($old)]);
    $originalHash=$user->password;
    $this->actingAs($user);
    foreach ([
        ['wrong password','A valid new passphrase!','A valid new passphrase!','current_password'],
        ['','','','current_password'],
        [$old,'short','short','password'],
        [$old,$old,$old,'password'],
        [$old,'A valid new passphrase!','different confirmation','password'],
        [$old,'password123456','password123456','password'],
    ] as [$current,$new,$confirm,$error]) {
        // Reset the limiter only to test independent validation cases.
        Illuminate\Support\Facades\RateLimiter::clear('account-password:'.sha1($user->id));
        $this->put('/account/password',['current_password'=>$current,'password'=>$new,'password_confirmation'=>$confirm])->assertSessionHasErrors($error,null,'passwordUpdate');
    }
    expect($user->fresh()->password)->toBe($originalHash);
    expect(ActivityLog::where('action','Password Changed')->exists())->toBeFalse();
});

it('checks breached passwords using only a hash prefix and never flashes password input', function () {
    $old='Current correct passphrase 42!'; $new='A breached passphrase 12345!';
    $user=User::factory()->create(['role'=>'admin','password'=>Hash::make($old)]);
    $hash=strtoupper(sha1($new));
    config(['testing.pwned_body'=>substr($hash,5).':30']);
    $this->actingAs($user)->put('/account/password',['current_password'=>$old,'password'=>$new,'password_confirmation'=>$new])->assertSessionHasErrors('password',null,'passwordUpdate');
    Http::assertSent(fn($request)=>$request->url()==='https://api.pwnedpasswords.com/range/'.substr($hash,0,5));
    expect(session()->getOldInput())->not->toHaveKeys(['password','current_password','password_confirmation']);
});

it('rate limits repeated password change attempts', function () {
    $user=User::factory()->create(['role'=>'staff']);
    $this->actingAs($user);
    for($i=0;$i<5;$i++) $this->put('/account/password',[])->assertStatus(302);
    $this->put('/account/password',[])->assertStatus(429);
});

it('removes database sessions for this user while preserving unrelated sessions', function () {
    $user=User::factory()->create(['role'=>'staff','password'=>Hash::make('Current password 123!')]);
    $other=User::factory()->create(['role'=>'admin']);
    foreach (['own-old-session'=>$user->id,'other-user-session'=>$other->id] as $id=>$userId) DB::table('sessions')->insert(['id'=>$id,'user_id'=>$userId,'payload'=>base64_encode(serialize([])),'last_activity'=>time()]);
    config(['session.driver'=>'database']);
    $this->actingAs($user)->put('/account/password',['current_password'=>'Current password 123!','password'=>'A new safe passphrase 123!','password_confirmation'=>'A new safe passphrase 123!'])->assertSessionHas('password_success');
    $this->assertDatabaseMissing('sessions',['id'=>'own-old-session']);
    $this->assertDatabaseHas('sessions',['id'=>'other-user-session']);
    $this->get('/account')->assertOk();
});

it('does not allow resident portal accounts to use personnel update endpoints', function () {
    $user=User::factory()->create(['role'=>'resident']);
    $this->actingAs($user)->get('/account')->assertOk();
    $this->patch('/account',[])->assertForbidden();
    $this->put('/account/password',[])->assertForbidden();
});
