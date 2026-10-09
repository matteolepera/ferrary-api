<?php

namespace App\Enums;

enum PowertrainType: string
{
    case Termico = 'termico';
    case Ibrido = 'ibrido';
    case Elettrico = 'elettrico';
}
