<?php

namespace App\Enums;

enum EvidenceState: string
{
    case Missing = 'missing';
    case Pending = 'pending';
    case Verified = 'verified';
}
