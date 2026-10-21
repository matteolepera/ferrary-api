<?php

namespace App\Enums;

enum ProductionType: string
{
    case Serie = 'serie';
    case SerieLimitata = 'serie_limitata';
    case OneOff = 'one_off';
}
