<?php

namespace App\Enums;

enum ReturnResolution: string
{
    case RefundRequested = 'refund_requested';
    case Exchange = 'exchange';
    case NoFinancialAction = 'no_financial_action';
}
