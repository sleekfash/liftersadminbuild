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
            'branch' => $this->whenLoaded('branch', fn () => ['id' => $this->branch->id, 'name' => $this->branch->name, 'code' => $this->branch->code]),
            'outgoing_user_id' => $this->outgoing_user_id,
            'outgoing' => $this->whenLoaded('outgoing', fn () => ['id' => $this->outgoing?->id, 'name' => $this->outgoing?->name]),
            'incoming_user_id' => $this->incoming_user_id,
            'incoming' => $this->whenLoaded('incoming', fn () => ['id' => $this->incoming?->id, 'name' => $this->incoming?->name]),
            'status' => $this->status,
            'snapshot' => $this->snapshot,
            'outgoing_signed_at' => $this->outgoing_signed_at,
            'outgoing_notes' => $this->outgoing_notes,
            'incoming_signed_at' => $this->incoming_signed_at,
            'incoming_notes' => $this->incoming_notes,
            'dispute_reason' => $this->dispute_reason,
            'approved_by' => $this->approved_by,
            'approver' => $this->whenLoaded('approver', fn () => ['id' => $this->approver?->id, 'name' => $this->approver?->name]),
            'approved_at' => $this->approved_at,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at,
            'audit_trail' => $this->whenLoaded('auditLogs', fn () => $this->auditLogs->map(fn ($entry) => [
                'id' => $entry->id,
                'event' => $entry->event_type,
                'summary' => $entry->summary,
                'actor_id' => $entry->actor_id,
                'actor_name' => $entry->actor?->name,
                'at' => $entry->created_at?->toIso8601String(),
            ])->values()),
        ];
    }
}
