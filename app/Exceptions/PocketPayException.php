<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * A Pocket Pay rule was broken (wrong PIN, insufficient balance, limit reached…).
 * The message is safe to show to the attendant, parent or admin.
 */
class PocketPayException extends RuntimeException
{
    public function __construct(string $message, public readonly int $status = 422, public readonly ?string $errorCode = null)
    {
        parent::__construct($message);
    }

    public function render()
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
            'error' => $this->errorCode,
        ], $this->status);
    }
}
