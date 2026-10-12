<?php

use App\Models\{Alert,User,ActivityLog};
use App\Support\EmergencyRecordForm;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function emergencyPayload(): array
{
    return ['report_source'=>'Barangay logbook','report_date_time'=>'2026-09-20T10:00','report_type'=>'initial','emergency_type'=>'flood','location'=>'Purok 2','incident_date'=>'2026-09-19','incident_time'=>'09:30','message'=>'Floodwater entered several homes.','verification_status'=>'pending'];
}

it('renders the emergency form and saves a private record without casualties or effects', function () {
    $admin=User::factory()->create(['role'=>'admin']);
    $this->actingAs($admin)->get('/admin/emergency/create')->assertOk()->assertSee('Add Emergency Record')->assertSee('Effects of Emergency')->assertSee('Record Verification')->assertDontSee('type="file"',false);
    $this->post('/admin/emergency',emergencyPayload())->assertSessionHasNoErrors()->assertRedirect()->assertSessionHas('success','Emergency record successfully saved.');
    $record=Alert::firstOrFail();
    expect($record->created_by)->toBe($admin->id)->and($record->is_record)->toBeTrue()->and($record->is_active)->toBeFalse();
    foreach (EmergencyRecordForm::groups() as $relation=>$group) expect($record->$relation()->count())->toBe(0);
    $this->get('/admin/emergency/'.$record->id)->assertOk()->assertSee('Saturday')->assertSee($record->message);
    $this->get('/admin/emergency')->assertOk()->assertSee('Pending')->assertSee('Edit');
    $this->get('/admin/emergency?search=Purok')->assertOk()->assertViewHas('alerts',fn ($rows)=>$rows->total()===1);
});

it('stores every related category with multiple casualties and server calculated house totals',function () {
    $staff=User::factory()->create(['role'=>'staff']); $entries=[];
    foreach (EmergencyRecordForm::groups() as $key=>$group) {
        $row=[];
        foreach ($group['fields'] as $name=>$field) $row[$name]=match($field['type']) {
            'integer','age','computed'=>2,'decimal'=>'12.50','datetime-local'=>'2026-09-20T11:00','select'=>array_key_first($field['options']),default=>str_ends_with($name, '_name') ? 'Saved Name' : 'Saved '.$name,
        };
        $entries[$key]=[$row];
    }
    $entries['casualties'][]=['casualty_type'=>'injured','name'=>'Second Person','age'=>'','sex'=>'Female'];
    $entries['casualties'][]=['casualty_type'=>'missing','name'=>'Third Person'];
    $entries['damagedHouses'][0]['total']=999;
    $data=array_replace(emergencyPayload(),['entries_json'=>json_encode($entries),'verification_status'=>'verified','date_verified'=>'2026-09-20T12:00','validated_by'=>$staff->id,'checked_by'=>$staff->id,'verified_by'=>999,'created_by'=>999,'is_active'=>true,'report_type'=>'progress','progress_report_no'=>'2']);
    $this->actingAs($staff)->post('/staff/emergency',$data)->assertSessionHasNoErrors();
    $record=Alert::firstOrFail();
    expect($record->created_by)->toBe($staff->id)->and($record->verified_by)->toBe($staff->id)->and($record->is_active)->toBeFalse()->and($record->casualties)->toHaveCount(3)->and($record->casualties[1]->age)->toBeNull()->and($record->damagedHouses->first()->total)->toBe(4);
    foreach (EmergencyRecordForm::groups() as $group) $this->assertDatabaseHas($group['table'],['emergency_record_id'=>$record->id]);
    $this->get('/staff/emergency/'.$record->id)->assertOk()->assertSee('Second Person')->assertSee('Saved commodity')->assertSee('Verification recorded by');
    $this->get('/staff/emergency/'.$record->id.'/edit')->assertOk()->assertSee('Second Person')->assertSee('2026-09-20T11:00');
    $record->delete();
    foreach (EmergencyRecordForm::groups() as $group) expect(DB::table($group['table'])->count())->toBe(0);
});

it('edits entries and clears stale progress and verification values without changing the creator',function () {
    $creator=User::factory()->create(['role'=>'admin']);
    $this->actingAs($creator)->post('/admin/emergency',array_replace(emergencyPayload(),['report_type'=>'progress','progress_report_no'=>'3','verification_status'=>'verified','entries_json'=>json_encode(['casualties'=>[['casualty_type'=>'injured','name'=>'Old Name']]])]))->assertSessionHasNoErrors();
    $record=Alert::firstOrFail();
    $staff=User::factory()->create(['role'=>'staff']);
    $this->actingAs($staff)->put('/staff/emergency/'.$record->id.'/record',emergencyPayload()+['entries_json'=>json_encode(['damagedHouses'=>[['site'=>'Purok 1','totally_damaged'=>2,'partially_damaged'=>1]]])])->assertSessionHasNoErrors();
    $record->refresh();
    expect($record->created_by)->toBe($creator->id)->and($record->verified_by)->toBeNull()->and($record->date_verified)->toBeNull()->and($record->progress_report_no)->toBeNull()->and($record->casualties()->count())->toBe(0)->and($record->damagedHouses->first()->total)->toBe(3);
});

it('validates entries and restores form data with inline errors',function () {
    $data=array_replace(emergencyPayload(),['report_source'=>'','report_type'=>'progress','validated_by'=>999,'entries_json'=>json_encode(['casualties'=>[['casualty_type'=>'injured','name'=>'Retained Person','age'=>-1]],'powerOutages'=>[['site'=>'Purok','interrupted_at'=>'2026-09-20T12:00','restored_at'=>'2026-09-20T10:00']]])]);
    $this->actingAs(User::factory()->create(['role'=>'admin']))->from('/admin/emergency/create')->post('/admin/emergency',$data)->assertSessionHasErrors(['report_source','progress_report_no','validated_by','entries.casualties.0.age','entries.powerOutages.0.restored_at']);
    expect(Alert::count())->toBe(0);
    $this->get('/admin/emergency/create')->assertOk()->assertSee('Retained Person')->assertSee('er-error');
});

it('restricts emergency records to admin and staff and never publishes internal records',function () {
    $this->get('/admin/emergency/create')->assertRedirect('/login');
    $this->post('/admin/emergency',emergencyPayload())->assertRedirect('/login');
    $admin=User::factory()->create(['role'=>'admin']);
    $this->actingAs($admin)->post('/admin/emergency',emergencyPayload())->assertSessionHasNoErrors();
    $record=Alert::firstOrFail();
    $this->patch('/admin/emergency/'.$record->id.'/toggle')->assertForbidden();
    $this->put('/admin/emergency/'.$record->id,['title'=>'Try publish','message'=>'Hidden','level'=>'high'])->assertForbidden();
    // Even a legacy integration setting active cannot expose internal records.
    $record->update(['is_active'=>true]);
    Alert::create(['title'=>'Public legacy notice','message'=>'Public information','level'=>'low','is_active'=>true,'created_by'=>$admin->id]);
    $resident=User::factory()->create(['role'=>'resident']);
    $this->actingAs($resident)->get('/admin/emergency')->assertForbidden();
    $this->get('/admin/emergency/create')->assertForbidden();
    $this->get('/admin/emergency/'.$record->id)->assertForbidden();
    $this->get('/admin/emergency/'.$record->id.'/edit')->assertForbidden();
    $this->post('/admin/emergency',emergencyPayload())->assertForbidden();
    $this->put('/admin/emergency/'.$record->id.'/record',emergencyPayload())->assertForbidden();
    $this->get('/resident/alerts')->assertOk()->assertViewHas('alerts',fn ($rows)=>$rows->count()===1 && !$rows->first()->is_record);
});

it('rejects resident accounts as validators and checkers and invalid json',function () {
    $resident=User::factory()->create(['role'=>'resident']);
    $this->actingAs(User::factory()->create(['role'=>'admin']))->post('/admin/emergency',emergencyPayload()+['validated_by'=>$resident->id,'checked_by'=>$resident->id,'entries_json'=>'not json'])->assertSessionHasErrors(['validated_by','checked_by','entries_json']);
    expect(Alert::count())->toBe(0);
});

it('rolls back the main record and all children if activity logging fails',function () {
    $this->actingAs(User::factory()->create(['role'=>'admin']));
    ActivityLog::creating(function () { throw new RuntimeException('Simulated logging failure'); });
    $this->withoutExceptionHandling();
    try {
        $this->post('/admin/emergency',emergencyPayload()+['entries_json'=>json_encode(['casualties'=>[['casualty_type'=>'injured','name'=>'Rollback Person']]])]);
        $this->fail('Expected failure');
    } catch (RuntimeException $exception) { expect($exception->getMessage())->toBe('Simulated logging failure'); }
    finally { ActivityLog::flushEventListeners(); }
    expect(Alert::count())->toBe(0)->and(DB::table('emergency_casualties')->count())->toBe(0);
});

it('preserves existing records and custom emergency types',function () {
    $admin=User::factory()->create(['role'=>'admin']);
    Alert::create(['title'=>'Legacy record','message'=>'Original details','level'=>'high','created_by'=>$admin->id]);
    Alert::create(['title'=>'Custom record','message'=>'Custom details','level'=>'low','created_by'=>$admin->id,'emergency_type'=>'landslide']);
    $this->actingAs($admin)->get('/admin/emergency')->assertOk()->assertSee('Legacy record')->assertSee('Original details');
    $this->get('/admin/emergency/create')->assertOk()->assertSee('Landslide');
    $this->post('/admin/emergency',array_replace(emergencyPayload(),['emergency_type'=>'landslide']))->assertSessionHasNoErrors();
});
