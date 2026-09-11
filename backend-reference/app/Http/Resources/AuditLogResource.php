<?php
namespace App\Http\Resources;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
class AuditLogResource extends JsonResource
{
    public function toArray(Request $request): array { return ['id' => $this->id, 'event_type' => $this->event_type, 'summary' => $this->summary, 'details' => $this->details, 'actor_id' => $this->actor_id, 'created_at' => $this->created_at]; }
}
