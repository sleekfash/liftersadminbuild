<?php
namespace App\Enums;
enum AuditEventType: string
{
    case CREATE = 'CREATE';
    case UPDATE = 'UPDATE';
    case STATUS_CHANGE = 'STATUS_CHANGE';
    case APPROVAL = 'APPROVAL';
    case AUTHORIZATION = 'AUTHORIZATION';
    case PAYMENT = 'PAYMENT';
    case REVERSAL = 'REVERSAL';
    case PERIOD_CLOSE = 'PERIOD_CLOSE';
    case PERIOD_LOCK = 'PERIOD_LOCK';
    case IMPORT = 'IMPORT';
    case OVERRIDE = 'OVERRIDE';
    case BLOCKED_ACTION = 'BLOCKED_ACTION';
}
