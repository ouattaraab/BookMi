<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case OrangeMoney  = 'orange_money';
    case Wave         = 'wave';
    case MtnMomo      = 'mtn_momo';
    case MoovMoney    = 'moov_money';
    case Card         = 'card';
    case BankTransfer = 'bank_transfer';

    /** Returns true if this method goes through Mobile Money flow. */
    public function isMobileMoney(): bool
    {
        return in_array($this, [
            self::OrangeMoney,
            self::Wave,
            self::MtnMomo,
            self::MoovMoney,
        ], strict: true);
    }

    /** Maps to Paystack mobile_money provider code. */
    public function paystackProvider(): ?string
    {
        return match ($this) {
            self::OrangeMoney => 'orange',
            self::Wave        => 'wave',
            self::MtnMomo     => 'mtn',
            self::MoovMoney   => 'moov',
            default           => null,
        };
    }
}
