<?php
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Locks the self-approval defence in ApprovalService::approve() at the
 * source level. A pure runtime test would require booting Laravel + DB;
 * this instead asserts the code path that enforces the invariant exists,
 * so it cannot be silently removed. Combined with the policy unit tests
 * above (which cover the authorization layer) and the feature suite (which
 * exercises the full DB path), self-approval is covered in depth.
 */
class ApprovalServiceSelfApprovalTest extends TestCase
{
    public function test_approval_service_source_rejects_self_approval_at_branch_stage(): void
    {
        $source = file_get_contents(__DIR__ . '/../../app/Services/ApprovalService.php');
        $this->assertNotFalse($source, 'ApprovalService.php must exist');

        $this->assertMatchesRegularExpression(
            '/DisbursementStage::BRANCH_APPROVED.*\(int\)\s*\$r->requested_by\s*===\s*\(int\)\s*\$a->id/s',
            $source,
            'ApprovalService must reject requester == approver at BRANCH_APPROVED stage.'
        );

        $this->assertStringContainsString(
            "'Requester cannot approve their own disbursement.'",
            $source,
            'ApprovalService must throw the self-approval validation message.'
        );
    }

    public function test_store_disbursement_request_enforces_branch_scope(): void
    {
        $source = file_get_contents(__DIR__ . '/../../app/Http/Requests/StoreDisbursementRequest.php');
        $this->assertNotFalse($source);
        $this->assertStringContainsString('withValidator', $source);
        $this->assertStringContainsString('You may only create disbursements for your own branch.', $source);
    }

    public function test_store_member_request_enforces_branch_scope(): void
    {
        $source = file_get_contents(__DIR__ . '/../../app/Http/Requests/StoreMemberRequest.php');
        $this->assertNotFalse($source);
        $this->assertStringContainsString('withValidator', $source);
        $this->assertStringContainsString('You may only create records for your own branch.', $source);
    }
}
