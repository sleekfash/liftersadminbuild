<?php
namespace App\Enums;
enum MemberStatus: string
{
    case PENDING = 'PENDING';
    case VERIFIED = 'VERIFIED';
    case ACTIVE = 'ACTIVE';
    case SUSPENDED = 'SUSPENDED';
    case TERMINATED = 'TERMINATED';
}
