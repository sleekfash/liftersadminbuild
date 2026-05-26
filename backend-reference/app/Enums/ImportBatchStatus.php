<?php
namespace App\Enums;
enum ImportBatchStatus: string
{
    case UPLOADED = 'UPLOADED';
    case MAPPED = 'MAPPED';
    case VALIDATED = 'VALIDATED';
    case POSTED = 'POSTED';
    case FAILED = 'FAILED';
}
