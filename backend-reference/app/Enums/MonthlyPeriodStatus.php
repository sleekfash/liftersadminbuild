<?php
namespace App\Enums;
enum MonthlyPeriodStatus: string
{
    case OPEN = 'OPEN';
    case REVIEW = 'REVIEW';
    case CLOSED = 'CLOSED';
    case LOCKED = 'LOCKED';
}
