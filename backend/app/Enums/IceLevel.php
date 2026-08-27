<?php

namespace App\Enums;

enum IceLevel: string
{
    case NoIce = 'no_ice';
    case LessIce = 'less_ice';
    case NormalIce = 'normal_ice';
    case ExtraIce = 'extra_ice';
}
