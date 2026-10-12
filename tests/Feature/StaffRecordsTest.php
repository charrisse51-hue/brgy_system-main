<?php

use App\Models\{Alert, Incident, Resident, User};
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function staffDisasterData(): array
{
    return ['type'=>'flood', 'location'=>'Staff Purok', 'description'=>'Staff incident narrative',
        'record_date'=>'2026-09-20', 'record_time'=>'10:00', 'complainant_name'=>'Reporting Resident',
        'incident_date'=>'2026-09-19', 'incident_time'=>'09:00',
        'persons'=>[['name'=>'Person One','age'=>30,'address'=>'Doyong']], 'remarks'=>'Follow up'];
}
function staffEmergencyData(): array
{
    return ['report_source'=>'Staff logbook','report_date_time'=>'2026-09-20T10:00', 'report_type'=>'initial',
        'emergency_type'=>'flood','location'=>'Staff Purok','incident_date'=>'2026-09-19','incident_time'=>'09:00',
        'message'=>'Staff emergency narrative','verification_status'=>'pending',
        'entries_json'=>json_encode(['casualties'=>[['casualty_type'=>'injured','name'=>'Injured Resident']]])];
}
function staffResidentData(): array
{
    return ['_resident_form'=>'individual','_resident_mode'=>'add','first_name'=>'Staff','last_name'=>'Resident',
        'barangay'=>'Doyong','status'=>'active','civil_status'=>'single','citizenship'=>'Filipino'];
}

it('serves all Staff record lists and forms with Staff navigation and the shared emergency tabs', function () {
    $this->actingAs(User::factory()->create(['role'=>'staff']));
    foreach (['/staff/disaster','/staff/disaster/create','/staff/emergency','/staff/emergency/create','/staff/residents','/staff/staffdashboard'] as $url) {
        $response=$this->get($url)->assertOk()->assertSee('RECORDS STAFF')
            ->assertSee(route('staff.incidents.index'))->assertSee(route('staff.emergency.index'))->assertSee(route('staff.residents.index'))
            ->assertDontSee('User Management')->assertDontSee('href="'.url('/admin/'),false)->assertDontSee('action="'.url('/admin/'),false);
        if ($url!=='/staff/staffdashboard') $response->assertSee('aria-current="page"',false);
    }
    $this->get('/staff/emergency/create')->assertSee('Emergency Information')->assertSee('Incident Details')
        ->assertSee('Casualties &amp; Effects',false)->assertSee('Response &amp; Actions',false)->assertSee('role="tab"',false);
    $this->get('/staff/residents/create')->assertOk()->assertSee('resident-form-page');
    $this->get('/staff/incidentmanagement')->assertRedirect(route('staff.incidents.index'));
});

it('creates views and edits shared disaster records without replacing the original author or status', function () {
    $admin=User::factory()->create(['role'=>'admin']);
    $this->actingAs($admin)->post('/admin/incidents',staffDisasterData())->assertSessionHasNoErrors();
    $record=Incident::firstOrFail();
    $record->update(['status'=>'ongoing']);
    $staff=User::factory()->create(['role'=>'staff']);
    $this->actingAs($staff)->get('/staff/disaster/'.$record->id)->assertOk()->assertSee('Person One');
    $this->get('/staff/disaster/'.$record->id.'/edit')->assertOk()->assertSee('Edit Disaster Record')->assertSee('Staff incident narrative');
    $this->put('/staff/disaster/'.$record->id,array_replace(staffDisasterData(),['description'=>'Updated by Staff']))
        ->assertSessionHasNoErrors()->assertRedirect(route('staff.incidents.index'));
    expect($record->fresh()->description)->toBe('Updated by Staff')->and($record->fresh()->user_id)->toBe($admin->id)
        ->and($record->fresh()->status)->toBe('ongoing')->and($record->fresh()->logbook['recorded_by'])->toBe($admin->name);
    $this->post('/staff/disaster',staffDisasterData())->assertSessionHasNoErrors()->assertRedirect(route('staff.incidents.index'));
    $this->patch('/staff/disaster/'.$record->id.'/status',['status'=>'resolved'])->assertRedirect();
    expect($record->fresh()->status)->toBe('resolved');
    $this->actingAs($admin)->get('/admin/incidents/'.$record->id.'/edit')->assertOk()->assertSee('Updated by Staff');
});

it('creates views and edits emergency records under Staff URLs using the same database', function () {
    $staff=User::factory()->create(['role'=>'staff']);
    $this->actingAs($staff)->post('/staff/emergency',staffEmergencyData())->assertSessionHasNoErrors();
    $record=Alert::firstOrFail();
    $this->get('/staff/emergency/'.$record->id)->assertOk()->assertSee('Injured Resident');
    $this->get('/staff/emergency/'.$record->id.'/edit')->assertOk()->assertSee('Staff emergency narrative');
    $this->put('/staff/emergency/'.$record->id.'/record',array_replace(staffEmergencyData(),['message'=>'Updated emergency']))
        ->assertSessionHasNoErrors()->assertRedirect(route('staff.emergency.show',$record));
    expect($record->fresh()->message)->toBe('Updated emergency')->and($record->fresh()->created_by)->toBe($staff->id);
    $this->actingAs(User::factory()->create(['role'=>'admin']))->get('/admin/emergency/'.$record->id)->assertOk()->assertSee('Updated emergency');
});

it('creates views and edits residents using shared forms and Staff redirect targets', function () {
    $this->actingAs(User::factory()->create(['role'=>'staff']))->post('/staff/residents',staffResidentData())->assertSessionHasNoErrors();
    $record=Resident::firstOrFail();
    $this->get('/staff/residents/'.$record->id)->assertRedirect(route('staff.residents.index',['view_id'=>$record->id]));
    $this->get('/staff/residents?view_id='.$record->id)->assertOk()->assertSee('residentViewData');
    $this->get('/staff/residents/'.$record->id.'/edit')->assertOk()->assertSee('resident-form-page')->assertSee(route('staff.residents.update',$record));
    $this->get('/staff/residents?edit_id='.$record->id)->assertRedirect(route('staff.residents.edit',$record));
    $this->from('/staff/residents?edit_id='.$record->id)->put('/staff/residents/'.$record->id,array_replace(staffResidentData(),['last_name'=>'Updated','_resident_mode'=>'edit','_resident_id'=>$record->id]))
        ->assertSessionHasNoErrors()->assertRedirect('/staff/residents?view_id='.$record->id);
    expect($record->fresh()->last_name)->toBe('Updated');
});

it('preserves failed Staff form input and shows validation errors on all three modules', function () {
    $this->actingAs(User::factory()->create(['role'=>'staff']));
    $this->from('/staff/disaster/create')->post('/staff/disaster',array_replace(staffDisasterData(),['location'=>'']))
        ->assertSessionHasErrors('location')->assertSessionHasInput('description','Staff incident narrative');
    $this->get('/staff/disaster/create')->assertOk()->assertSee('Please correct the following')->assertSee('Staff incident narrative');
    $this->from('/staff/emergency/create')->post('/staff/emergency',array_replace(staffEmergencyData(),['report_source'=>'']))
        ->assertSessionHasErrors('report_source')->assertSessionHasInput('message','Staff emergency narrative');
    $this->get('/staff/emergency/create')->assertOk()->assertSee('Please correct the highlighted fields');
    $this->from('/staff/residents/create')->post('/staff/residents',array_replace(staffResidentData(),['first_name'=>'']))->assertSessionHasErrors('first_name');
    $this->get('/staff/residents/create')->assertOk()->assertSee('data-restore="1"',false);
    expect(Incident::count())->toBe(0)->and(Alert::count())->toBe(0)->and(Resident::count())->toBe(0);
});

it('filters and paginates all Staff record lists with Staff URLs and query parameters', function () {
    $user=User::factory()->create(['role'=>'staff']);
    for($i=0;$i<16;$i++) {
        Incident::create(['user_id'=>$user->id,'type'=>'flood','location'=>'Match Purok '.$i,'status'=>'pending']);
        Alert::create(['created_by'=>$user->id,'title'=>'Match Emergency '.$i,'message'=>'Narrative','is_record'=>true,'location'=>'Match Purok','verification_status'=>'pending']);
        Resident::create(['name'=>'Match Resident '.$i,'first_name'=>'Match','last_name'=>'Resident '.$i,'address'=>'Doyong','status'=>'active']);
    }
    $this->actingAs($user);
    foreach (['disaster'=>'incidents','emergency'=>'alerts','residents'=>'residents'] as $path=>$variable) {
        $this->get('/staff/'.$path.'?search=Match')->assertOk()->assertViewHas($variable,fn($rows)=>$rows->total()===16 && str_contains($rows->nextPageUrl(),'/staff/'.$path) && str_contains($rows->nextPageUrl(),'search=Match'));
        $this->get('/staff/'.$path.'?search=Match&page=2')->assertOk()->assertViewHas($variable,fn($rows)=>$rows->currentPage()===2 && $rows->count()>0);
        $this->get('/staff/'.$path.'?search=DoesNotExist')->assertOk()->assertViewHas($variable,fn($rows)=>$rows->total()===0);
    }
    $this->get('/staff/disaster?type=fire&status=pending')->assertOk()->assertViewHas('incidents',fn($rows)=>$rows->total()===0);
    $this->get('/staff/residents?status=inactive')->assertOk()->assertViewHas('residents',fn($rows)=>$rows->total()===16);
});

it('enforces role boundaries and does not grant Staff delete archive or Admin privileges', function () {
    foreach (['/staff/disaster','/staff/emergency','/staff/residents','/staff/staffdashboard'] as $path) $this->get($path)->assertRedirect('/login');
    $staff=User::factory()->create(['role'=>'staff']);
    $this->actingAs($staff);
    foreach (['/admin/dashboard','/admin/incidents','/admin/emergency','/admin/residents','/admin/users','/admin/archives','/admin/activity-logs','/admin/summary-reports'] as $path) $this->get($path)->assertForbidden();
    foreach (['/admin/incidents','/admin/emergency','/admin/residents','/admin/users'] as $path) $this->post($path,[])->assertForbidden();
    $incident=Incident::create(['user_id'=>$staff->id,'type'=>'flood','location'=>'Doyong','status'=>'resolved']);
    $alert=Alert::create(['created_by'=>$staff->id,'title'=>'Protected','message'=>'Protected']);
    $this->patch('/admin/incidents/'.$incident->id.'/archive')->assertForbidden();
    $this->delete('/admin/emergency/'.$alert->id)->assertForbidden();
    $this->patch('/staff/disaster/'.$incident->id.'/archive')->assertNotFound();
    $this->delete('/staff/emergency/'.$alert->id)->assertStatus(405);
    expect($incident->fresh()->archived)->toBeFalse()->and($alert->fresh())->not->toBeNull();
    foreach (['admin','resident'] as $role) {
        $this->actingAs(User::factory()->create(['role'=>$role]));
        foreach (['/staff/disaster','/staff/emergency','/staff/residents','/staff/staffdashboard'] as $path) $this->get($path)->assertForbidden();
        foreach (['/staff/disaster','/staff/emergency','/staff/residents'] as $path) $this->post($path,[])->assertForbidden();
    }
});
