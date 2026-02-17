<?php

namespace App\Exceptions;

use Exception;

class BookmiException extends Exception
{
    protected string $errorCode;
    protected int $statusCode;
    protected array $details;

    public function __construct(
        string $errorCode,
        string $message,
        int $statusCode = 422,
        array $details = [],
        ?Exception $previous = null,
    ) {
        parent::__construct($message, 0, $previous);

        $this->errorCode = $errorCode;
        $this->statusCode = $statusCode;
        $this->details = $details;
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getDetails(): array
    {
        return $this->details;
    }

    public function toArray(): array
    {
        return [
            'error' => [
                'code' => $this->errorCode,
                'message' => $this->message,
                'status' => $this->statusCode,
                'details' => $this->details ?: new \stdClass(),
            ],
        ];
    }
}
