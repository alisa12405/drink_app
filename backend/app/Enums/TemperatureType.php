<?php

namespace App\Enums;

enum TemperatureType: string
{
    case Hot = 'hot';
    case Cold = 'cold';
    case Both = 'both';
}
