<?php
namespace App\Enums;

enum HandoverStatus: string
{
    case DRAFT = 'DRAFT';
    case PENDING_INCOMING = 'PENDING_INCOMING';
    case PENDING_APPROVAL = 'PENDING_APPROVAL';
    case COMPLETED = 'COMPLETED';
    case DISPUTED = 'DISPUTED';
}
