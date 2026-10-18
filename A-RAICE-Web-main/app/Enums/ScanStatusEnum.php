<?php

namespace App\Enums;

enum ScanStatusEnum: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Completed = 'completed';
    case Failed = 'failed';
}
