<?php
namespace App\Services;

use App\Enums\AuditEventType;
use App\Enums\HandoverStatus;
use App\Models\Branch;
use App\Models\BranchHandover;
use App\Models\DisbursementRequest;
use App\Models\ImportBatch;
use App\Models\Member;
use App\Models\MonthlyPeriod;
use App\Models\ReconciliationItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BranchHandoverService
{
    public function __construct(private AuditService $auditService) {}

    public function create(Branch $branch, array $data, User $actor): BranchHandover
    {
        return DB::transaction(function () use ($branch, $data, $actor) {
            $open = $branch->handovers()
                ->whereNotIn('status', [HandoverStatus::COMPLETED->value, HandoverStatus::DISPUTED->value])
                ->exists();
            if ($open) {
                throw ValidationException::withMessages(['branch_id' => 'An open handover already exists for this branch.']);
            }

            $handover = BranchHandover::create([
                'branch_id' => $branch->id,
                'outgoing_user_id' => $data['outgoing_user_id'] ?? $branch->current_manager_id,
                'incoming_user_id' => $data['incoming_user_id'],
                'status' => HandoverStatus::DRAFT->value,
                'snapshot' => $this->snapshot($branch),
                'created_by' => $actor->id,
            ]);

            $this->auditService->record($actor, AuditEventType::HANDOVER, 'Branch handover opened', $handover, [
                'branch_id' => $branch->id,
            ]);

            return $handover->fresh();
        });
    }

    /** Outgoing officer signs off the frozen report. */
    public function signOutgoing(BranchHandover $handover, ?string $notes, User $actor): BranchHandover
    {
        $this->assertStatus($handover, [HandoverStatus::DRAFT->value]);

        $handover->forceFill([
            'outgoing_signed_at' => now(),
            'outgoing_notes' => $notes,
            'status' => HandoverStatus::PENDING_INCOMING->value,
        ])->save();

        $this->auditService->record($actor, AuditEventType::HANDOVER, 'Outgoing officer signed handover', $handover);

        return $handover->fresh();
    }

    /** Incoming officer consents to the report. */
    public function acknowledgeIncoming(BranchHandover $handover, ?string $notes, User $actor): BranchHandover
    {
        $this->assertStatus($handover, [HandoverStatus::PENDING_INCOMING->value]);

        $handover->forceFill([
            'incoming_signed_at' => now(),
            'incoming_notes' => $notes,
            'status' => HandoverStatus::PENDING_APPROVAL->value,
        ])->save();

        $this->auditService->record($actor, AuditEventType::HANDOVER, 'Incoming officer acknowledged handover', $handover);

        return $handover->fresh();
    }

    public function dispute(BranchHandover $handover, string $reason, User $actor): BranchHandover
    {
        $this->assertStatus($handover, [
            HandoverStatus::DRAFT->value,
            HandoverStatus::PENDING_INCOMING->value,
            HandoverStatus::PENDING_APPROVAL->value,
        ]);

        $handover->forceFill([
            'status' => HandoverStatus::DISPUTED->value,
            'dispute_reason' => $reason,
        ])->save();

        $this->auditService->record($actor, AuditEventType::HANDOVER, 'Branch handover disputed', $handover, [
            'reason' => $reason,
        ]);

        return $handover->fresh();
    }

    public function approve(BranchHandover $handover, User $actor): BranchHandover
    {
        $this->assertStatus($handover, [HandoverStatus::PENDING_APPROVAL->value]);

        $handover->forceFill([
            'status' => HandoverStatus::COMPLETED->value,
            'approved_by' => $actor->id,
            'approved_at' => now(),
        ])->save();

        $this->auditService->record($actor, AuditEventType::HANDOVER, 'Branch handover approved and completed', $handover);

        return $handover->fresh();
    }

    /** Printable/exportable signed report payload. */
    public function report(BranchHandover $handover): array
    {
        $handover->loadMissing(['branch', 'outgoing', 'incoming', 'approver']);

        return [
            'handover_id' => $handover->id,
            'status' => $handover->status,
            'branch' => ['id' => $handover->branch?->id, 'name' => $handover->branch?->name, 'code' => $handover->branch?->code],
            'outgoing_officer' => $handover->outgoing?->only(['id', 'name', 'email']),
            'incoming_officer' => $handover->incoming?->only(['id', 'name', 'email']),
            'snapshot' => $handover->snapshot,
            'signatures' => [
                'outgoing' => ['signed_at' => $handover->outgoing_signed_at, 'notes' => $handover->outgoing_notes],
                'incoming' => ['signed_at' => $handover->incoming_signed_at, 'notes' => $handover->incoming_notes],
                'approved' => ['by' => $handover->approver?->only(['id', 'name']), 'at' => $handover->approved_at],
            ],
            'dispute_reason' => $handover->dispute_reason,
            'generated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Freeze the position of the branch portfolio at handover time. This is the
     * document both officers sign against; it is never recomputed afterwards.
     */
    public function snapshot(Branch $branch): array
    {
        return [
            'captured_at' => now()->toIso8601String(),
            'members_by_status' => Member::query()->where('branch_id', $branch->id)
                ->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status')->all(),
            'members_total' => Member::query()->where('branch_id', $branch->id)->count(),
            'disbursements_by_stage' => DisbursementRequest::query()->where('branch_id', $branch->id)
                ->selectRaw('stage, count(*) as total')->groupBy('stage')->pluck('total', 'stage')->all(),
            'open_disbursement_value' => (float) DisbursementRequest::query()->where('branch_id', $branch->id)
                ->whereNull('paid_at')->sum('amount'),
            'unposted_import_batches' => ImportBatch::query()->where('branch_id', $branch->id)
                ->where('status', '!=', 'POSTED')->count(),
            'open_reconciliation_items' => ReconciliationItem::query()
                ->whereNull('resolved_at')
                ->whereHas('relatedDisbursementRequest', fn ($q) => $q->where('branch_id', $branch->id))
                ->count(),
            'latest_closed_period' => MonthlyPeriod::query()->whereNotNull('closed_at')
                ->latest('closed_at')->value('id'),
        ];
    }

    private function assertStatus(BranchHandover $handover, array $allowed): void
    {
        if (!in_array($handover->status, $allowed, true)) {
            throw ValidationException::withMessages([
                'status' => "Action not allowed while the handover is {$handover->status}.",
            ]);
        }
    }
}
