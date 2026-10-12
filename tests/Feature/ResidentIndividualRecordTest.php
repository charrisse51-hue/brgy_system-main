<?php

use App\Models\{Resident, User};
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create(['role'=>'admin']));
});

function individualResidentPayload(): array
{
    return [
        '_resident_form'=>'individual', '_resident_mode'=>'add',
        'first_name'=>'Ana', 'middle_name'=>'Cruz', 'last_name'=>'Santos', 'status'=>'active',
        'barangay'=>'Doyong', 'sitio_purok'=>'Purok 2', 'house_number'=>'12', 'street'=>'Main Street',
        'citizenship'=>'Filipino', 'birthdate'=>'1960-01-01', 'civil_status'=>'annulled',
        'income_sources'=>['Salary/Wage','Pension'], 'monthly_income'=>'12500.50',
        'target_groups'=>['PWD','Senior Citizen'], 'pwd_classification'=>'Others', 'pwd_other'=>'Example classification',
        'senior_citizen_number'=>'SC-12', 'registered_voter'=>'1', 'last_year_voted'=>2025, 'voter_location'=>'Doyong',
        'out_of_school_youth'=>'0', 'can_read_write'=>'1', 'height_cm'=>'155.5', 'weight_kg'=>'50.2',
        'blood_type'=>'O+', 'email'=>'ana@example.test', 'mother_last_name'=>'Cruz',
    ];
}

it('stores the expanded form and maintains legacy name and address', function () {
    $this->post('/admin/residents', individualResidentPayload())->assertSessionHasNoErrors()->assertRedirect();
    $resident = Resident::firstOrFail();
    expect($resident->name)->toBe('Ana Cruz Santos')
        ->and($resident->address)->toBe('12, Main Street, Purok 2, Doyong')
        ->and($resident->income_sources)->toBe(['Salary/Wage','Pension'])
        ->and($resident->target_groups)->toBe(['PWD','Senior Citizen'])
        ->and($resident->registered_voter)->toBeTrue()
        ->and($resident->out_of_school_youth)->toBeFalse()
        ->and($resident->civil_status)->toBe('annulled');
    $this->get('/admin/residents')->assertOk()->assertSee('Ana Cruz Santos')->assertSee('resident-individual-form.js')->assertSee('data-resident=', false);
    $this->get('/admin/residents?search=Ana&civil_status=annulled')->assertOk()->assertViewHas('residents', fn ($rows) => $rows->total() === 1);
});

it('updates saved values and clears deselected conditional fields', function () {
    $this->post('/admin/residents', individualResidentPayload());
    $resident = Resident::firstOrFail();
    $data = individualResidentPayload();
    $data['_resident_mode'] = 'edit';
    $data['_resident_id'] = $resident->id;
    $data['target_groups'] = [];
    $data['income_sources'] = [];
    $data['registered_voter'] = '0';
    $data['last_name'] = 'Reyes';
    $this->put('/admin/residents/'.$resident->id, $data)->assertSessionHasNoErrors();
    $resident->refresh();
    expect($resident->last_name)->toBe('Reyes')->and($resident->income_sources)->toBe([])
        ->and($resident->target_groups)->toBe([])->and($resident->pwd_classification)->toBeNull()
        ->and($resident->pwd_other)->toBeNull()->and($resident->senior_citizen_number)->toBeNull()
        ->and($resident->last_year_voted)->toBeNull()->and($resident->voter_location)->toBeNull();
});

it('rejects invalid values and restores edit input on the full-page form', function () {
    $this->post('/admin/residents', individualResidentPayload());
    $resident = Resident::firstOrFail();
    $data = individualResidentPayload();
    $data['_resident_mode'] = 'edit';
    $data['_resident_id'] = $resident->id;
    $data['birthdate'] = now()->addDay()->toDateString();
    $data['pwd_other'] = '';
    $data['income_sources'] = ['Invalid source'];
    $data['monthly_income'] = -1;
    $this->from('/admin/residents/'.$resident->id.'/edit')->put('/admin/residents/'.$resident->id, $data)
        ->assertRedirect('/admin/residents/'.$resident->id.'/edit')->assertSessionHasErrors(['birthdate','pwd_other','income_sources.0','monthly_income'])
        ->assertSessionHasInput('first_name', 'Ana');
    $this->get('/admin/residents/'.$resident->id.'/edit')->assertOk()->assertSee('data-restore="1"', false)->assertSee('Please correct these fields');
    expect($resident->fresh()->birthdate->format('Y-m-d'))->toBe('1960-01-01');
});

it('keeps old records compatible and opens create and details in the existing module', function () {
    $resident = Resident::create(['name'=>'Legacy Resident','first_name'=>'Legacy','last_name'=>'Resident','address'=>'Original address','status'=>'active']);
    $this->get('/admin/residents/create')->assertOk()->assertSee('resident-form-page')->assertSee('Save Resident');
    $this->get('/admin/residents/'.$resident->id.'/edit')->assertOk()->assertSee('resident-form-page')->assertSee('Save Changes')->assertSee(route('admin.residents.update', $resident));
    $this->get('/admin/residents/'.$resident->id)->assertRedirect('/admin/residents?view_id='.$resident->id);
    $this->get('/admin/residents?view_id='.$resident->id)->assertOk()->assertSee('residentViewData')->assertSee('Original address');
    $this->put('/admin/residents/'.$resident->id, ['first_name'=>'Legacy','last_name'=>'Resident','address'=>'Original address','status'=>'active'])->assertSessionHasNoErrors();
    expect($resident->fresh()->address)->toBe('Original address')->and(Resident::count())->toBe(1);
});

it('renders the split-panel interface with left list and right profile details sections', function () {
    $resident = Resident::create([
        'name' => 'Juan Dela Cruz',
        'first_name' => 'Juan',
        'last_name' => 'Dela Cruz',
        'barangay' => 'Doyong',
        'sitio_purok' => 'Sitio Centro',
        'address' => 'Sitio Centro, Doyong',
        'status' => 'active',
        'civil_status' => 'married',
        'sex' => 'Male',
        'birthdate' => '1990-05-15',
    ]);

    $response = $this->get('/admin/residents');
    $response->assertOk();
    $response->assertSee('res-split-container');
    $response->assertSee('List of Registered Residents');
    $response->assertSee('Resident Profile Details');
    $response->assertSee('Section A: Address Information');
    $response->assertSee('Section B: Personal Information');
    $response->assertSee('Section C: Vulnerable / Target Groups');
    $response->assertSee('Section D: Contact &amp; Identity', false);
    $response->assertSee('Section E: Record Information');
    $response->assertSee('Juan Dela Cruz');
    $response->assertSee('Sitio Centro');
    $response->assertSee('splitAddResidentBtn');
    $response->assertSee('splitEditResidentBtn');
    $response->assertDontSee('splitArchiveResidentBtn');
    $response->assertDontSee('res-stat-strip');
    $response->assertDontSee('resProfileStatusPill');
    $response->assertDontSee('resVal_status');
    $response->assertDontSee('name="status"', false);
});

it('preserves resident records when removed archive and delete endpoints are requested', function () {
    $resident = Resident::create(['name'=>'Maria Clara', 'first_name'=>'Maria', 'last_name'=>'Clara', 'address'=>'Doyong', 'status'=>'active']);
    $this->patch('/admin/residents/'.$resident->id.'/archive')->assertNotFound();
    $this->delete('/admin/residents/'.$resident->id)->assertStatus(405);
    expect($resident->fresh()->status)->toBe('active');
    $this->assertDatabaseHas('residents', ['id'=>$resident->id]);
});

it('ignores resident status changes submitted through the edit form', function () {
    $resident = Resident::create(['name'=>'Maria Clara', 'first_name'=>'Maria', 'last_name'=>'Clara', 'address'=>'Doyong', 'status'=>'inactive']);
    $this->put('/admin/residents/'.$resident->id, individualResidentPayload())
        ->assertSessionHasNoErrors()->assertRedirect();
    expect($resident->fresh()->status)->toBe('inactive');
});

it('preserves a resident record when its linked account is requested for deletion', function () {
    $user = User::factory()->create(['role'=>'resident']);
    $resident = Resident::create(['name'=>'Maria Clara', 'address'=>'Doyong']);
    $resident->user()->associate($user);
    $resident->save();
    $this->delete('/admin/users/'.$user->id)->assertNotFound();
    $this->assertDatabaseHas('users', ['id'=>$user->id]);
    $this->assertDatabaseHas('residents', ['id'=>$resident->id, 'user_id'=>$user->id]);
});

it('combines resident name search with the sitio filter including older sitio names', function () {
    foreach (['Sitio Gonzales', 'Gonzales', 'Sitio Central'] as $sitio) {
        Resident::create(['name'=>'Ana Santos', 'first_name'=>'Ana', 'last_name'=>'Santos', 'address'=>'Doyong', 'sitio_purok'=>$sitio]);
    }
    Resident::create(['name'=>'Maria Clara', 'first_name'=>'Maria', 'last_name'=>'Clara', 'address'=>'Doyong', 'sitio_purok'=>'Sitio Gonzales']);
    $this->get('/admin/residents?search=Ana&sitio=Sitio%20Gonzales')
        ->assertOk()->assertViewHas('residents', fn ($rows) => $rows->total() === 2);
    $this->get('/admin/residents?search=NoMatchingResident')
        ->assertOk()->assertViewHas('residents', fn ($rows) => $rows->total() === 0);
    $this->get('/admin/residents?sitio=Unknown')->assertSessionHasErrors('sitio');
});
