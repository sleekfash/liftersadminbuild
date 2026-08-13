<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BranchManagerAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id', 'user_id', 'role_code', 'started_at', 'ended_at', 'ended_reason',
        'assigned_by', 'branch_handover_id', 'handover_overridden',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'handover_overridden' => 'boolean',
    ];

    public function branch() { return $this->belongsTo(Branch::class); }
    public function user() { return $this->belongsTo(User::class); }
    public function assignedBy() { return $this->belongsTo(User::class, 'assigned_by'); }
    public function handover() { return $this->belongsTo(BranchHandover::class, 'branch_handover_id'); }

    public function scopeActive($query) { return $query->whereNull('ended_at'); }
}
