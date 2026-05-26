<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class DisbursementRequest extends Model
{
    use HasFactory;
    protected $fillable = ['member_id', 'branch_id', 'requested_by', 'amount', 'purpose', 'stage', 'authorized_by', 'authorized_at', 'paid_at', 'rejected_reason', 'cancelled_reason'];
    protected $casts = [
        'metadata'=>'array','details'=>'array','raw_payload'=>'array','mapped_payload'=>'array','errors'=>'array',
        'title_blocks'=>'array','heading_map'=>'array','response_body'=>'array',
        'is_active'=>'boolean','approved_at'=>'datetime','authorized_at'=>'datetime','paid_at'=>'datetime',
        'activated_at'=>'datetime','terminated_at'=>'datetime','closed_at'=>'datetime','locked_at'=>'datetime',
        'completed_at'=>'datetime','resolved_at'=>'datetime','locked_at'=>'datetime','occurred_on'=>'date'
    ];
    public function member(){return $this->belongsTo(Member::class);} public function branch(){return $this->belongsTo(Branch::class);} public function approvals(){return $this->hasMany(DisbursementApproval::class);} public function statusHistories(){return $this->hasMany(DisbursementStatusHistory::class);} public function treasuryTransactions(){return $this->hasMany(TreasuryTransaction::class);}
}
