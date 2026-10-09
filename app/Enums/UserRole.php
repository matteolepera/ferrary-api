<?php

namespace App\Enums;

enum UserRole: string
{
    case Master = 'master';
    case Admin = 'admin';
    case Moderator = 'moderator';
}
