<?php
namespace App\Services;
use App\Enums\AuditEventType; use App\Models\AuditLog; use App\Models\User; use Illuminate\Database\Eloquent\Model;
class AuditService
{
    public function record(?User $actor, AuditEventType|string $eventType, string $summary, ?Model $target=null, array $details=[]): AuditLog
    {
        return AuditLog::create(['actor_id'=>$actor?->id,'event_type'=>$eventType instanceof AuditEventType?$eventType->value:$eventType,'auditable_type'=>$target?$target::class:null,'auditable_id'=>$target?->id,'summary'=>$summary,'details'=>$details,'ip_address'=>request()?->ip(),'user_agent'=>request()?->userAgent()]);
    }
}
