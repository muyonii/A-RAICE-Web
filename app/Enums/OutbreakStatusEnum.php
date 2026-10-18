<?php

namespace App\Enums;

enum OutbreakStatusEnum: string
{
    case Active = 'active';
    case Resolved = 'resolved';
}
