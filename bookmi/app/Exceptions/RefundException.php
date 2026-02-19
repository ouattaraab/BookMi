<?php

namespace App\Exceptions;

class RefundException extends BookmiException
{
    public static function amountExceedsTransaction(int $refundAmount, int $transactionAmount): self
    {
        return new self(
            'REFUND_AMOUNT_EXCEEDS_TRANSACTION',
            "Le montant du remboursement ({$refundAmount} XOF) dépasse le montant de la transaction ({$transactionAmount} XOF).",
        );
    }

    public static function transactionNotRefundable(string $status): self
    {
        return new self(
            'REFUND_TRANSACTION_NOT_REFUNDABLE',
            "La transaction ne peut pas être remboursée (statut: {$status}).",
        );
    }

    public static function noSucceededTransaction(): self
    {
        return new self(
            'REFUND_NO_SUCCEEDED_TRANSACTION',
            'Aucune transaction réussie trouvée pour cette réservation.',
        );
    }
}
