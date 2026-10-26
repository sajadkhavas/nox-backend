<?php

namespace App\Enums;

enum ReturnStatus: string
{
    case Requested = 'requested';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Received = 'received';
    case Resolved = 'resolved';
    case Cancelled = 'cancelled';

    public function terminal(): bool
    {
        return in_array($this, [self::Rejected, self::Resolved, self::Cancelled], true);
    }
}
