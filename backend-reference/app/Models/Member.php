<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class Member extends Model
{
    use HasFactory;
    protected $fillable = ['branch_id', 'created_by', 'updated_by', 'umid', 'first_name', 'last_name', 'phone', 'email', 'status', 'bank_name', 'bank_account_number', 'id_document_ref', 'status_reason', 'activated_at', 'terminated_at'];
    protected $casts = [
        'metadata'=>'array','details'=>'array','raw_payload'=>'array','mapped_payload'=>'array','errors'=>'array',
        'title_blocks'=>'array','heading_map'=>'array','response_body'=>'array',
        'is_active'=>'boolean','approved_at'=>'datetime','authorized_at'=>'datetime','paid_at'=>'datetime',
        'activated_at'=>'datetime','terminated_at'=>'datetime','closed_at'=>'datetime','locked_at'=>'datetime',
        'completed_at'=>'datetime','resolved_at'=>'datetime','locked_at'=>'datetime','occurred_on'=>'date'
    ];
    public function branch(){return $this->belongsTo(Branch::class);} public function statusHistories(){return $this->hasMany(MemberStatusHistory::class);} public function disbursementRequests(){return $this->hasMany(DisbursementRequest::class);}
}
