<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Extract next-action fields from gateway response so Flutter can handle OTP prompts
        $gatewayResponse = $this->gateway_response ?? [];

        return [
            'id'                => $this->id,
            'booking_id'        => $this->booking_request_id,
            'payment_method'    => $this->payment_method->value,
            'amount'            => $this->amount,
            'currency'          => $this->currency,
            'gateway'           => $this->gateway,
            'gateway_reference' => $this->gateway_reference,
            'status'            => $this->status->value,
            // Paystack Charge API next-action fields (e.g. send_otp → display prompt)
            'gateway_status'    => $gatewayResponse['status'] ?? null,
            'display_text'      => $gatewayResponse['display_text'] ?? null,
            'initiated_at'      => $this->initiated_at?->toISOString(),
            'completed_at'      => $this->completed_at?->toISOString(),
        ];
    }
}
