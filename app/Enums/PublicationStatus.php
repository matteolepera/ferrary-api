<?php

namespace App\Enums;

enum PublicationStatus: string
{
    case Draft = 'draft';
    case Scheduled = 'scheduled';
    case Published = 'published';
}
