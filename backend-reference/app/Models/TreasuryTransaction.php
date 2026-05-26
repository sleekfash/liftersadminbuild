<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class TreasuryTransaction extends Model
{
    use HasFactory;
    protected $fillable = ['monthly_period_id', 'disbursement_request_id', 'created_by', 'reversed_transaction_id', 'type', 'amount', 'balance_after', 'description', 'remarks', 'occurred_on'];
    protected $casts = [
        'metadata'=>'array','details'=>'array','raw_payload'=>'array','mapped_payload'=>'array','errors'=>'array',
        'title_blocks'=>'array','heading_map'=>'array','response_body'=>'array',
        'is_active'=>'boolean','approved_at'=>'datetime','authorized_at'=>'datetime','paid_at'=>'datetime',
        'activated_at'=>'datetime','terminated_at'=>'datetime','closed_at'=>'datetime','locked_at'=>'datetime',
        'completed_at'=>'datetime','resolved_at'=>'datetime','locked_at'=>'datetime','occurred_on'=>'date'
    ];
    public function monthlyPeriod(){return $this->belongsTo(MonthlyPeriod::class);} public function disbursementRequest(){return $this->belongsTo(DisbursementRequest::class);}
}
