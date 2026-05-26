<?php
namespace App\Enums;
enum DisbursementStage: string
{
    case DRAFT = 'DRAFT';
    case SUBMITTED = 'SUBMITTED';
    case BRANCH_APPROVED = 'BRANCH_APPROVED';
    case FINANCE_REVIEWED = 'FINANCE_REVIEWED';
    case AUTHORIZED = 'AUTHORIZED';
    case PAID = 'PAID';
    case CANCELLED = 'CANCELLED';
    case REJECTED = 'REJECTED';
}
