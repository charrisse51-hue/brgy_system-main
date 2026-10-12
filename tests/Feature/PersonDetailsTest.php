<?php

use App\Models\{Incident, Alert, User};
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function personRecordPayload(): array {
    return ['type'=>'flood','location'=>'Doyong','description'=>'Flood response.','record_date'=>'2026-09-20','record_time'=>'10:00','complainant_first_name'=>'Ana','complainant_last_name'=>'Cruz','incident_date'=>'2026-09-19','incident_time'=>'09:00'];
}

it('saves independent Unicode name parts and edits or removes people without losing others', function () {
    $this->actingAs(User::factory()->create(['role'=>'admin']));
    $people = [
        ['first_name'=>'  José Luis  ','middle_name'=>'Santos','last_name'=>"O'Neal-Cruz",'age'=>0,'address'=>'#12, Purok 3 / Doyong'],
        ['first_name'=>'María','middle_name'=>'','last_name'=>'Dela Cruz','age'=>120,'address'=>'Doyong'],
        ['first_name'=>'Ana','middle_name'=>'','last_name'=>'Reyes','age'=>'','address'=>''],
    ];
    $this->post('/admin/incidents',personRecordPayload()+['persons'=>$people])->assertSessionHasNoErrors();
    $record=Incident::firstOrFail();
    expect($record->logbook['persons'])->toHaveCount(3)
        ->and($record->logbook['persons'][0]['first_name'])->toBe('José Luis')
        ->and($record->logbook['persons'][0]['name'])->toBe("José Luis Santos O'Neal-Cruz");
    $number=$record->record_number;
    $this->get('/admin/incidents/'.$record->id)->assertOk()->assertSee('María Dela Cruz');
    $this->get('/admin/incidents/'.$record->id.'/edit')->assertOk()->assertSee('value="Dela Cruz"',false);
    $people[2]['first_name']='Anabel';
    array_splice($people,1,1);
    $this->put('/admin/incidents/'.$record->id,personRecordPayload()+['persons'=>$people])->assertSessionHasNoErrors();
    $record->refresh();
    expect($record->logbook['persons'])->toHaveCount(2)
        ->and($record->logbook['persons'][0]['first_name'])->toBe('José Luis')
        ->and($record->logbook['persons'][1]['first_name'])->toBe('Anabel')
        ->and($record->record_number)->toBe($number);
});

it('keeps respondents optional but validates every populated row independently',function () {
    $this->actingAs(User::factory()->create(['role'=>'admin']));
    $this->post('/admin/incidents',personRecordPayload()+['persons'=>[['first_name'=>'','middle_name'=>'','last_name'=>'','age'=>'','address'=>'']]])->assertSessionHasNoErrors();
    expect(Incident::first()->logbook['persons'])->toBe([]);
    $this->from('/admin/incidents/create')->post('/admin/incidents',personRecordPayload()+['persons'=>[
        ['first_name'=>'Juan','last_name'=>'Cruz'],
        ['middle_name'=>'Santos','age'=>0],
    ]])->assertSessionHasErrors(['persons.1.first_name','persons.1.last_name'])->assertSessionDoesntHaveErrors(['persons.0.first_name','persons.0.last_name']);
    $this->get('/admin/incidents/create')->assertOk()->assertSee('error-person-1-first_name')->assertSee('value="Juan"',false);
});

it('rejects invalid respondent values on the server', function ($field,$value) {
    $this->actingAs(User::factory()->create(['role'=>'admin']));
    $person=['first_name'=>'Juan','middle_name'=>'','last_name'=>'Cruz','age'=>20,'address'=>'Doyong'];
    $person[$field]=$value;
    $this->post('/admin/incidents',personRecordPayload()+['persons'=>[$person]])->assertSessionHasErrors('persons.0.'.$field);
    expect(Incident::count())->toBe(0);
})->with([
    ['first_name','J'], ['first_name','Juan2'], ['first_name','@Juan'], ['first_name',str_repeat('a',51)],
    ['middle_name','123'], ['middle_name',str_repeat('a',51)], ['last_name','C'], ['last_name','Cruz!'],
    ['age',-1], ['age','2.5'], ['age',121], ['age','1e2'], ['address','<script>'], ['address',str_repeat('a',256)],
]);

it('preserves legacy full names and renders no example placeholders in either form',function () {
    $user=User::factory()->create(['role'=>'admin']); $this->actingAs($user);
    $record=Incident::create(['user_id'=>$user->id,'type'=>'flood','location'=>'Doyong','status'=>'pending','logbook'=>['persons'=>[['name'=>'Juan Dela Cruz','age'=>33,'address'=>'Doyong']]]]);
    $this->get('/admin/incidents/'.$record->id.'/edit')->assertOk()->assertSee('value="Juan Dela"',false)->assertSee('value="Cruz"',false)->assertDontSee('placeholder=',false);
    $this->get('/admin/incidents/'.$record->id)->assertOk()->assertSee('Juan Dela Cruz');
    $this->get('/admin/emergency/create')->assertOk()->assertDontSee('placeholder=',false);
    if ($directory=getenv('PERSON_FIXTURE_DIR')) {
        if (!is_dir($directory)) mkdir($directory,0700,true);
        file_put_contents($directory.'/disaster.html',$this->get('/admin/incidents/create')->getContent());
        file_put_contents($directory.'/emergency.html',$this->get('/admin/emergency/create')->getContent());
    }
});

it('retains exact emergency casualty name parts through save edit and removal',function () {
    $this->actingAs(User::factory()->create(['role'=>'admin']));
    $data=['report_source'=>'Barangay','report_date_time'=>'2026-09-20T10:00','report_type'=>'initial','emergency_type'=>'flood','location'=>'Doyong','incident_date'=>'2026-09-19','incident_time'=>'09:00','message'=>'Flood response.','verification_status'=>'pending'];
    $people=[['casualty_type'=>'injured','first_name'=>'Juan Miguel','middle_name'=>'Santos','last_name'=>'Dela Cruz','age'=>0,'address'=>'Doyong'],['casualty_type'=>'missing','first_name'=>'María','last_name'=>'Reyes']];
    $this->post('/admin/emergency',$data+['entries_json'=>json_encode(['casualties'=>$people])])->assertSessionHasNoErrors();
    $record=Alert::firstOrFail();
    expect($record->casualties)->toHaveCount(2)->and($record->casualties[0]->middle_name)->toBe('Santos')->and($record->casualties[0]->last_name)->toBe('Dela Cruz');
    $this->get('/admin/emergency/'.$record->id)->assertOk()->assertSee('Juan Miguel Santos Dela Cruz');
    $this->get('/admin/emergency/'.$record->id.'/edit')->assertOk()->assertSee('Dela Cruz');
    array_pop($people); $people[0]['middle_name']='Ramos';
    $this->put('/admin/emergency/'.$record->id.'/record',$data+['entries_json'=>json_encode(['casualties'=>$people])])->assertSessionHasNoErrors();
    expect($record->fresh()->casualties)->toHaveCount(1)->and($record->fresh()->casualties[0]->name)->toBe('Juan Miguel Ramos Dela Cruz');
    $people[0]['age']='2.5'; $people[0]['first_name']='Juan2';
    $this->put('/admin/emergency/'.$record->id.'/record',$data+['entries_json'=>json_encode(['casualties'=>$people])])->assertSessionHasErrors(['entries.casualties.0.age','entries.casualties.0.first_name']);
    expect($record->fresh()->casualties[0]->name)->toBe('Juan Miguel Ramos Dela Cruz');
});
