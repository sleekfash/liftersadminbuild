<?php
namespace App\Enums;
enum TreasuryTransactionType: string
{
    case OPENING_BALANCE = 'OPENING_BALANCE';
    case CASH_RECEIVED = 'CASH_RECEIVED';
    case DISBURSEMENT_PAID = 'DISBURSEMENT_PAID';
    case CASH_RETURNED = 'CASH_RETURNED';
    case ADJUSTMENT_IN = 'ADJUSTMENT_IN';
    case ADJUSTMENT_OUT = 'ADJUSTMENT_OUT';
    case REVERSAL = 'REVERSAL';
}
