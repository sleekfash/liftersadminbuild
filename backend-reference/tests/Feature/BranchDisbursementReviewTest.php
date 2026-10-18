<?php
namespace Tests\Feature;

use App\Models\{Branch,DisbursementRequest,Member,Role,User};
use App\Services\ApprovalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class BranchDisbursementReviewTest extends TestCase
{
    use RefreshDatabase;
    private function fixture(): array
    {
        $branch=Branch::create(['name'=>'Lagos','code'=>'LAG','is_active'=>true]);
        $role=Role::create(['name'=>'Branch Manager','code'=>'BRANCH_MANAGER']);
        $maker=User::create(['name'=>'Maker','email'=>'maker@example.test','password'=>'test-password','branch_id'=>$branch->id,'is_active'=>true]);
        $manager=User::create(['name'=>'Checker','email'=>'checker@example.test','password'=>'test-password','branch_id'=>$branch->id,'is_active'=>true]);
        $manager->roles()->attach($role);
        $member=Member::create(['branch_id'=>$branch->id,'umid'=>'UMID-001','first_name'=>'Grace','last_name'=>'Adebayo','status'=>'VERIFIED','phone'=>'08000000000']);
        $item=DisbursementRequest::create(['branch_id'=>$branch->id,'member_id'=>$member->id,'requested_by'=>$maker->id,'amount'=>100,'purpose'=>'Education','stage'=>'SUBMITTED']);
        return [$maker,$manager,$item];
    }
    private function decide(User $actor,DisbursementRequest $item,string $action,array $input)
    {
        return $this->actingAs($actor)->withHeader('Idempotency-Key', (string) \Illuminate\Support\Str::uuid())->postJson("/api/v1/disbursements/{$item->id}/{$action}",$input);
    }
    public function test_approval_records_optional_remarks_actor_and_transition(): void
    {
        [, $manager,$item]=$this->fixture();
        $this->decide($manager,$item,'approve/branch',[])->assertOk()->assertJsonPath('data.stage','BRANCH_APPROVED');
        $this->assertDatabaseHas('disbursement_approvals',['disbursement_request_id'=>$item->id,'user_id'=>$manager->id,'stage'=>'BRANCH_APPROVED']);
        $this->assertDatabaseHas('disbursement_status_histories',['disbursement_request_id'=>$item->id,'changed_by'=>$manager->id,'from_stage'=>'SUBMITTED','to_stage'=>'BRANCH_APPROVED']);
        $this->decide($manager,$item,'approve/branch',[])->assertForbidden();
    }
    public function test_approval_remarks_allow_1000_but_not_1001_characters(): void
    {
        [, $manager,$item]=$this->fixture();
        $this->decide($manager,$item,'approve/branch',['remarks'=>str_repeat('x',1001)])->assertUnprocessable();
        $this->decide($manager,$item,'approve/branch',['remarks'=>str_repeat('x',1000)])->assertOk();
    }
    public function test_rejection_requires_category_and_reason_between_15_and_1000(): void
    {
        [, $manager,$item]=$this->fixture();
        $this->decide($manager,$item,'reject/branch',['reason'=>str_repeat('x',15)])->assertUnprocessable();
        $this->decide($manager,$item,'reject/branch',['category'=>'UNKNOWN','reason'=>str_repeat('x',15)])->assertUnprocessable();
        foreach ([0,14,1001] as $length) $this->decide($manager,$item,'reject/branch',['category'=>'OTHER','reason'=>str_repeat('x',$length)])->assertUnprocessable();
        $this->decide($manager,$item,'reject/branch',['category'=>'OTHER','reason'=>str_repeat(' ',20)])->assertUnprocessable();
        $this->decide($manager,$item,'reject/branch',['category'=>'OTHER','reason'=>str_repeat('x',15)])->assertOk()->assertJsonPath('data.stage','REJECTED');
        $this->assertDatabaseHas('disbursement_requests',['id'=>$item->id,'stage'=>'REJECTED','rejection_category'=>'OTHER','rejected_reason'=>str_repeat('x',15)]);
        $this->assertDatabaseHas('disbursement_status_histories',['disbursement_request_id'=>$item->id,'from_stage'=>'SUBMITTED','to_stage'=>'REJECTED','changed_by'=>$manager->id]);
        $this->assertDatabaseHas('audit_logs',['actor_id'=>$manager->id,'event_type'=>'STATUS_CHANGE','auditable_id'=>$item->id]);
    }
    public function test_rejection_accepts_1000_character_reason(): void
    {
        [, $manager,$item]=$this->fixture();
        $this->decide($manager,$item,'reject/branch',['category'=>'OTHER','reason'=>str_repeat('x',1000)])->assertOk();
    }
    public function test_both_decisions_block_own_submissions_even_for_super_admin(): void
    {
        [$maker,,$item]=$this->fixture();
        $role=Role::create(['name'=>'Super Admin','code'=>'SUPER_ADMIN']);
        $maker->roles()->attach($role);
        $this->decide($maker,$item,'approve/branch',[])->assertForbidden();
        $this->decide($maker,$item,'reject/branch',['category'=>'OTHER','reason'=>'Insufficient documentation'])->assertForbidden();
        $this->assertDatabaseHas('disbursement_requests',['id'=>$item->id,'stage'=>'SUBMITTED']);
    }
    public function test_review_is_branch_role_and_stage_scoped(): void
    {
        [$maker,$manager,$item]=$this->fixture();
        $this->decide($maker,$item,'approve/branch',[])->assertForbidden();
        $foreign=Branch::create(['name'=>'Ibadan','code'=>'IBA','is_active'=>true]);
        $manager->update(['branch_id'=>$foreign->id]);
        $this->decide($manager,$item,'approve/branch',[])->assertForbidden();
        $this->decide($manager,$item,'reject/branch',['category'=>'OTHER','reason'=>'Insufficient documentation'])->assertForbidden();
        $manager->update(['branch_id'=>$item->branch_id]);
        foreach (['DRAFT','BRANCH_APPROVED','REJECTED'] as $stage) {
            $item->update(['stage'=>$stage]);
            $this->decide($manager,$item,'reject/branch',['category'=>'OTHER','reason'=>'Insufficient documentation'])->assertForbidden();
        }
    }
    public function test_service_rechecks_identity_without_controller_authorization(): void
    {
        [$maker,,$item]=$this->fixture();
        $this->expectException(ValidationException::class);
        app(ApprovalService::class)->branchReject($item,['category'=>'OTHER','reason'=>'Insufficient documentation'],$maker);
    }
}