<?php

namespace App\Enum;

enum SessionFormat: string
{
    case InPerson = 'in_person';
    case Online = 'online';

    public function label(): string
    {
        return match($this) {
            self::InPerson => 'In-person',
            self::Online => 'Live online',
        };
    }
}
