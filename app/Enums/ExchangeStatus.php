<?php

namespace App\Enums;

enum ExchangeStatus: string
{
    case Requested = 'requested';
    case Approved = 'approved';
    case Completed = 'completed';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';
}
