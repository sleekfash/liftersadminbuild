<?php
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BranchManagerAssignmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'branch_id' => $this->branch_id,
            'user_id' => $this->user_id,
            'role_code' => $this->role_code,
            'started_at' => $this->started_at,
            'ended_at' => $this->ended_at,
            'ended_reason' => $this->ended_reason,
            'assigned_by' => $this->assigned_by,
            'branch_handover_id' => $this->branch_handover_id,
            'handover_overridden' => (bool) $this->handover_overridden,
            'is_active' => $this->ended_at === null,
        ];
    }
}
