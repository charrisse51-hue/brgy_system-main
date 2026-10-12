<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\EmergencyRecordRequest;
use App\Models\{Alert,User,ActivityLog};
use App\Support\EmergencyRecordForm;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\{Arr,Str};
use Illuminate\Http\Request;

class EmergencyRecordController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(function ($request,$next) {
            abort_unless(in_array($request->user()?->role,['admin','staff'],true),403);
            return $next($request);
        });
    }

    public function create() { return $this->form(new Alert()); }
    public function edit(Alert $emergency) { abort_unless($emergency->is_record,404); return $this->form($emergency); }

    private function form(Alert $emergency)
    {
        $staff=User::whereIn('role',['admin','staff'])->orderBy('name')->get(['id','name']);
        $types=EmergencyRecordForm::types();
        $entries=[];
        if ($emergency->exists) foreach (EmergencyRecordForm::groups() as $key=>$group) {
            $entries[$key]=$emergency->$key->map(function ($row) use ($group) {
                $values=$row->only(array_keys($group['fields']));
                if ($row instanceof \App\Models\EmergencyCasualty) $values['name']=$row->name;
                if ($row instanceof \App\Models\EmergencyCasualty && !$row->first_name && !$row->last_name) $values = array_merge($values, \App\Support\PersonDetails::names(['name'=>$row->name]));
                foreach ($group['fields'] as $name=>$field) if ($field['type']==='datetime-local' && !empty($values[$name])) $values[$name]=\Carbon\Carbon::parse($values[$name])->format('Y-m-d\TH:i');
                return $values;
            })->all();
        }
        return view('admin.emergency_record_form',compact('emergency','staff','types','entries'));
    }

    public function show(Alert $emergency)
    {
        abort_unless($emergency->is_record,404);
        $emergency->load(array_merge(array_keys(EmergencyRecordForm::groups()),['creator','validator','checker','verifier']));
        return view('admin.emergency_record_show',compact('emergency'));
    }

    public function store(EmergencyRecordRequest $request) { return $this->save($request,new Alert()); }
    public function update(EmergencyRecordRequest $request, Alert $emergency)
    {
        abort_unless($emergency->is_record,404);
        return $this->save($request,$emergency);
    }

    private function save(EmergencyRecordRequest $request, Alert $emergency)
    {
        $data=$request->validated();
        $emergency=DB::transaction(function () use ($request,$emergency,$data) {
            $editing=$emergency->exists;
            $attributes=Arr::except($data,['entries','entries_json']);
            if (!empty($attributes['reporter_first_name']) || !empty($attributes['reporter_last_name'])) {
                $f = $attributes['reporter_first_name'] ?? '';
                $m = $attributes['reporter_middle_name'] ?? '';
                $l = $attributes['reporter_last_name'] ?? '';
                $attributes['reporter_name'] = trim("{$f} " . ($m ? "{$m} " : "") . $l);
            } elseif (!empty($attributes['reporter_name'])) {
                $parts = preg_split('/\s+/', trim($attributes['reporter_name']));
                $attributes['reporter_first_name'] = $parts[0] ?? null;
                $attributes['reporter_last_name'] = count($parts) > 1 ? end($parts) : ($parts[0] ?? null);
            }

            if (!empty($attributes['reporter_sitio']) || !empty($attributes['reporter_other_address'])) {
                $sitio = $attributes['reporter_sitio'] ?? '';
                $other = $attributes['reporter_other_address'] ?? '';
                $attributes['reporter_address'] = trim(($sitio ? $sitio : '') . ($other ? ($sitio ? ', ' : '') . $other : ''));
            }

            $attributes['progress_report_no']=$attributes['progress_report_no'] ?? null;
            $attributes['is_record']=true;
            $attributes['is_active']=false;
            $attributes['title']=Str::limit((EmergencyRecordForm::types()[$attributes['emergency_type']] ?? $attributes['emergency_type']).' — '.$attributes['location'],255,'');
            if (!$editing) $attributes['created_by']=$request->user()->id;
            if ($attributes['verification_status']==='verified') {
                $attributes['verified_by']=$request->user()->id;
                $attributes['checked_by'] ??= $request->user()->id;
                $attributes['date_verified']=$attributes['date_verified'] ?? now();
            } else {
                $attributes['verified_by']=null;
                $attributes['date_verified']=null;
            }
            $emergency->fill($attributes)->save();
            foreach (EmergencyRecordForm::groups() as $relation=>$group) {
                if ($editing) $emergency->$relation()->delete();
                foreach ($data['entries'][$relation] ?? [] as $row) {
                    foreach ($group['fields'] as $name=>$field) if ($field['type']==='datetime-local' && !empty($row[$name])) $row[$name]=\Carbon\Carbon::parse($row[$name])->format('Y-m-d H:i:s');
                    if ($relation==='casualties') $row['name'] = \App\Support\PersonDetails::fullName($row);
                    if ($relation==='damagedHouses') $row['total']=($row['totally_damaged'] ?? 0)+($row['partially_damaged'] ?? 0);
                    $emergency->$relation()->create($row);
                }
            }
            ActivityLog::create(['user_id'=>$request->user()->id,'action'=>$editing ? 'Emergency record updated' : 'Emergency record created','description'=>'Emergency record #'.$emergency->id.' — '.$emergency->location,'action_type'=>'update','model_type'=>'Alert','model_id'=>$emergency->id]);
            return $emergency;
        });
        return redirect()->route(\App\Support\RecordPortal::routeName('emergency.show'),$emergency)->with('success','Emergency record successfully saved.');
    }
}
