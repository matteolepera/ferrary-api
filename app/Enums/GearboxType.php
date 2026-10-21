<?php

namespace App\Enums;

enum GearboxType: string
{
    case Manuale = 'manuale';
    case Robotizzato = 'robotizzato';
    case DoppiaFrizione = 'doppia_frizione';
    case Sequenziale = 'sequenziale';
    case Automatico = 'automatico';
}
