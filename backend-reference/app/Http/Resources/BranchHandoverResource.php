<?php
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BranchHandoverResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'branch_id' => $this->branch_id,
            'outgoing_user_id' => $this->outgoing_user_id,
            'incoming_user_id' => $this->incoming_user_id,
            'status' => $this->status,
            'snapshot' => $this->snapshot,
            'outgoing_signed_at' => $this->outgoing_signed_at,
            'outgoing_notes' => $this->outgoing_notes,
            'incoming_signed_at' => $this->incoming_signed_at,
            'incoming_notes' => $this->incoming_notes,
            'dispute_reason' => $this->dispute_reason,
            'approved_by' => $this->approved_by,
            'approved_at' => $this->approved_at,
            'created_at' => $this->created_at,
        ];
    }
}
