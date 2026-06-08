<?php

namespace App\Enum;

enum BookingStatus: string
{
    case Booked = 'booked';
    case Waitlisted = 'waitlisted';
    case Attended = 'attended';
    case Cancelled = 'cancelled';
}
