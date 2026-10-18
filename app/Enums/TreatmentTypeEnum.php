<?php

namespace App\Enums;

enum TreatmentTypeEnum: string
{
    case Chemical = 'chemical';
    case Biological = 'biological';
    case Cultural = 'cultural';
    case Organic = 'organic';
}
