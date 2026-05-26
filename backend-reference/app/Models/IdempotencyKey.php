<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class IdempotencyKey extends Model
{
    use HasFactory;
    protected $fillable = ['user_id', 'key', 'method', 'path', 'request_hash', 'response_status', 'response_body', 'locked_at', 'completed_at'];
    protected $casts = [
        'metadata'=>'array','details'=>'array','raw_payload'=>'array','mapped_payload'=>'array','errors'=>'array',
        'title_blocks'=>'array','heading_map'=>'array','response_body'=>'array',
        'is_active'=>'boolean','approved_at'=>'datetime','authorized_at'=>'datetime','paid_at'=>'datetime',
        'activated_at'=>'datetime','terminated_at'=>'datetime','closed_at'=>'datetime','locked_at'=>'datetime',
        'completed_at'=>'datetime','resolved_at'=>'datetime','locked_at'=>'datetime','occurred_on'=>'date'
    ];

}
