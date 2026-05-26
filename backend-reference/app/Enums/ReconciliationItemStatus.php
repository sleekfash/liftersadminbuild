<?php
namespace App\Enums;
enum ReconciliationItemStatus: string
{
    case OPEN = 'OPEN';
    case UNDER_REVIEW = 'UNDER_REVIEW';
    case RESOLVED = 'RESOLVED';
    case OVERRIDDEN = 'OVERRIDDEN';
}
