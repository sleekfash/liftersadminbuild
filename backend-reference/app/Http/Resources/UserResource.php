<?php
namespace App\Http\Resources;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
class UserResource extends JsonResource
{
    public function toArray(Request $request): array { return ['id' => $this->id, 'name' => $this->name, 'email' => $this->email, 'is_active' => (bool) $this->is_active, 'branch' => $this->whenLoaded('branch', fn () => new BranchResource($this->branch)), 'branch_id' => $this->branch_id, 'roles' => $this->whenLoaded('roles', fn () => RoleResource::collection($this->roles)), 'created_at' => $this->created_at]; }
}
