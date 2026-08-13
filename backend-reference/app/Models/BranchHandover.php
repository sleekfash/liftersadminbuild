<?php
namespace App\Models;

use App\Enums\HandoverStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BranchHandover extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id', 'outgoing_user_id', 'incoming_user_id', 'status', 'snapshot',
        'outgoing_signed_at', 'outgoing_notes', 'incoming_signed_at', 'incoming_notes',
        'dispute_reason', 'approved_by', 'approved_at', 'created_by',
    ];

    protected $casts = [
        'snapshot' => 'array',
        'outgoing_signed_at' => 'datetime',
        'incoming_signed_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public function branch() { return $this->belongsTo(Branch::class); }
    public function outgoing() { return $this->belongsTo(User::class, 'outgoing_user_id'); }
    public function incoming() { return $this->belongsTo(User::class, 'incoming_user_id'); }
    public function approver() { return $this->belongsTo(User::class, 'approved_by'); }

    public function isCompleted(): bool
    {
        return $this->status === HandoverStatus::COMPLETED->value;
    }
}
