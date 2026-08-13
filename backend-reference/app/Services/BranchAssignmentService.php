<?php
namespace App\Services;

use App\Enums\AuditEventType;
use App\Enums\HandoverStatus;
use App\Models\Branch;
use App\Models\BranchHandover;
use App\Models\BranchManagerAssignment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Posting a branch manager moves the OFFICER ONLY.
 *
 * Branch-owned portfolios (members, disbursement requests, treasury
 * transactions, import batches, reconciliation runs) are keyed on their own
 * branch_id and are deliberately left untouched by every method here.
 */
class BranchAssignmentService
{
    public function __construct(private AuditService $auditService) {}

    /**
     * Post $manager to $branch, replacing the incumbent if there is one.
     */
    public function postManager(
        Branch $branch,
        User $manager,
        User $actor,
        ?BranchHandover $handover = null,
        bool $override = false,
        ?string $reason = null
    ): BranchManagerAssignment {
        return DB::transaction(function () use ($branch, $manager, $actor, $handover, $override, $reason) {
            $incumbent = $branch->activeManagerAssignment()->first();

            if ($incumbent && $incumbent->user_id === $manager->id) {
                throw ValidationException::withMessages([
                    'user_id' => 'This officer already manages the branch.',
                ]);
            }

            if ($incumbent) {
                $this->assertHandoverSatisfied($branch, $incumbent->user_id, $manager->id, $handover, $override);
                $this->closeAssignment($incumbent, $actor, $reason ?? 'Replaced by a new posting.');
            }

            // Close any active posting the incoming officer holds elsewhere.
            BranchManagerAssignment::query()->active()
                ->where('user_id', $manager->id)
                ->where('branch_id', '!=', $branch->id)
                ->get()
                ->each(fn (BranchManagerAssignment $a) => $this->closeAssignment(
                    $a, $actor, $reason ?? "Posted to branch {$branch->code}."
                ));

            $assignment = BranchManagerAssignment::create([
                'branch_id' => $branch->id,
                'user_id' => $manager->id,
                'role_code' => 'BRANCH_MANAGER',
                'started_at' => now(),
                'assigned_by' => $actor->id,
                'branch_handover_id' => $handover?->id,
                'handover_overridden' => $override,
            ]);

            // The officer's own posting changes; NOT the branch's records.
            $manager->forceFill(['branch_id' => $branch->id])->save();
            $branch->forceFill(['current_manager_id' => $manager->id])->save();

            $this->auditService->record($actor, AuditEventType::ASSIGNMENT, 'Branch manager posted', $branch, [
                'branch_id' => $branch->id,
                'incoming_user_id' => $manager->id,
                'outgoing_user_id' => $incumbent?->user_id,
                'handover_id' => $handover?->id,
                'handover_overridden' => $override,
                'records_moved' => false,
            ]);

            return $assignment->fresh(['branch', 'user']);
        });
    }

    public function unassignManager(Branch $branch, User $actor, ?string $reason = null): ?BranchManagerAssignment
    {
        return DB::transaction(function () use ($branch, $actor, $reason) {
            $current = $branch->activeManagerAssignment()->first();
            if (!$current) return null;

            $this->closeAssignment($current, $actor, $reason ?? 'Posting ended.');
            $branch->forceFill(['current_manager_id' => null])->save();

            $this->auditService->record($actor, AuditEventType::ASSIGNMENT, 'Branch manager posting ended', $branch, [
                'branch_id' => $branch->id,
                'outgoing_user_id' => $current->user_id,
                'records_moved' => false,
            ]);

            return $current->fresh();
        });
    }

    private function closeAssignment(BranchManagerAssignment $assignment, User $actor, string $reason): void
    {
        $assignment->forceFill([
            'ended_at' => now(),
            'ended_reason' => $reason,
        ])->save();

        $branch = $assignment->branch;
        if ($branch && $branch->current_manager_id === $assignment->user_id) {
            $branch->forceFill(['current_manager_id' => null])->save();
        }
    }

    /**
     * A replacement requires a COMPLETED handover between the two officers
     * unless an admin explicitly overrides (which is audited).
     */
    private function assertHandoverSatisfied(
        Branch $branch,
        ?int $outgoingId,
        int $incomingId,
        ?BranchHandover $handover,
        bool $override
    ): void {
        if ($override) return;

        $valid = $handover
            && $handover->branch_id === $branch->id
            && $handover->isCompleted()
            && (int) $handover->outgoing_user_id === (int) $outgoingId
            && (int) $handover->incoming_user_id === $incomingId;

        if (!$valid) {
            throw ValidationException::withMessages([
                'branch_handover_id' => 'A completed handover between the outgoing and incoming officer is required, or an explicit admin override.',
            ]);
        }

        if ($handover->status !== HandoverStatus::COMPLETED->value) {
            throw ValidationException::withMessages(['branch_handover_id' => 'Handover is not completed.']);
        }
    }
}
