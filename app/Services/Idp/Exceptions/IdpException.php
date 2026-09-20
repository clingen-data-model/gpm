<?php

namespace App\Services\Idp\Exceptions;

use RuntimeException;

/**
 * Raised when the identity provider rejects or fails a request.
 */
class IdpException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly ?int $status = null,
        public readonly array $errors = [],
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $status ?? 0, $previous);
    }

    public function isRateLimited(): bool
    {
        return $this->status === 429;
    }

    public function isConflict(): bool
    {
        return in_array($this->status, [409, 422], true);
    }
}
