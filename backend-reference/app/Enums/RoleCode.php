<?php
namespace App\Enums;
enum RoleCode: string
{
    case SUPER_ADMIN = 'SUPER_ADMIN';
    case SUB_ADMIN = 'SUB_ADMIN';
    case FINANCE_OFFICER = 'FINANCE_OFFICER';
    case BRANCH_MANAGER = 'BRANCH_MANAGER';
    case AUDITOR = 'AUDITOR';
}
