<?php

namespace App\Enums;

enum VehicleType: string
{
    case Stradale = 'stradale';
    case Pista = 'pista';
    case GtCorsa = 'gt_corsa';
    case Prototipo = 'prototipo';
    case F1 = 'f1';
    case Concept = 'concept';
}
