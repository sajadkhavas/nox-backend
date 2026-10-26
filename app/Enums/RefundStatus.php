<?php

namespace App\Enums;

enum RefundStatus: string
{
    case Requested = 'requested';
    case Pending = 'pending';
    case Completed = 'completed';
    case Failed = 'failed';
    case Partial = 'partial';
}
