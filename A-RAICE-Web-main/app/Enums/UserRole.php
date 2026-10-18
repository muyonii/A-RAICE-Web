<?php

namespace App\Enums;

enum UserRole: string
{
    case Farmer = 'farmer';
    case Admin = 'admin';
    case LguStaff = 'lgu_staff';
}
