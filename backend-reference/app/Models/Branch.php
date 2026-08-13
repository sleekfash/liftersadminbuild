<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class Branch extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'code', 'address', 'is_active', 'current_manager_id'];
    protected $casts = [
        'metadata'=>'array','details'=>'array','raw_payload'=>'array','mapped_payload'=>'array','errors'=>'array',
        'title_blocks'=>'array','heading_map'=>'array','response_body'=>'array',
        'is_active'=>'boolean','approved_at'=>'datetime','authorized_at'=>'datetime','paid_at'=>'datetime',
        'activated_at'=>'datetime','terminated_at'=>'datetime','closed_at'=>'datetime','locked_at'=>'datetime',
        'completed_at'=>'datetime','resolved_at'=>'datetime','locked_at'=>'datetime','occurred_on'=>'date'
    ];
    public function users(){return $this->hasMany(User::class);}
    public function members(){return $this->hasMany(Member::class);}
    public function disbursementRequests(){return $this->hasMany(DisbursementRequest::class);}
    public function importBatches(){return $this->hasMany(ImportBatch::class);}
    public function currentManager(){return $this->belongsTo(User::class,'current_manager_id');}
    public function managerAssignments(){return $this->hasMany(BranchManagerAssignment::class);}
    public function activeManagerAssignment(){return $this->hasOne(BranchManagerAssignment::class)->whereNull('ended_at');}
    public function handovers(){return $this->hasMany(BranchHandover::class);}
}
