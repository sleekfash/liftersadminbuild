<?php
namespace App\Enums;
enum ImportRowStatus: string
{
    case PENDING = 'PENDING';
    case MAPPED = 'MAPPED';
    case VALID = 'VALID';
    case INVALID = 'INVALID';
    case POSTED = 'POSTED';
    case SKIPPED = 'SKIPPED';
}
