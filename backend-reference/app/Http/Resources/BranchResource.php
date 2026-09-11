<?php
namespace App\Http\Resources;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
class BranchResource extends JsonResource
{
    public function toArray(Request $request): array { return ['id' => $this->id, 'name' => $this->name, 'code' => $this->code, 'address' => $this->address, 'is_active' => (bool) $this->is_active, 'current_manager' => $this->whenLoaded('currentManager', fn () => ['id' => $this->currentManager?->id, 'name' => $this->currentManager?->name, 'email' => $this->currentManager?->email])]; }
}
