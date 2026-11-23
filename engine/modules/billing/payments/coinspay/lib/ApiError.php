<?php

namespace CoinsPay\Dle;

use RuntimeException;

/**
 * An error of the CoinsPay API or of the way to it. From the FOSSBilling module (ApiError.php).
 *
 * @copyright Copyright (c) 2026 CoinsPay
 * @license   MIT; see LICENSE
 */
final class ApiError extends RuntimeException
{
    private int $status;

    private string $errorCode;

    private array $details;

    /**
     * @param int    $status    HTTP status, 0 without an answer.
     * @param string $errorCode Stable error code of the API, e.g. VALIDATION_ERROR, or NETWORK.
     * @param string $message   Explanation in English.
     * @param array  $details   Error details: invalid fields and the like.
     */
    public function __construct(int $status, string $errorCode, string $message, array $details = [])
    {
        parent::__construct($message !== '' ? $message : $errorCode);
        $this->status = $status;
        $this->errorCode = $errorCode;
        $this->details = $details;
    }

    public function status(): int
    {
        return $this->status;
    }

    public function errorCode(): string
    {
        return $this->errorCode;
    }

    public function details(): array
    {
        return $this->details;
    }

    /**
     * The invoice does not exist for this key: another shop, the other mode or a sample event.
     * Only the API says so with its error code; a bare 404 of a proxy or a wrong address does not.
     */
    public function isNotFound(): bool
    {
        return $this->status === 404 && $this->errorCode !== '' && !str_starts_with($this->errorCode, 'HTTP_');
    }

    /** One line for the log: code, message and details, never the key. */
    public function summary(): string
    {
        $line = $this->errorCode . ': ' . $this->getMessage();
        if ($this->details) {
            $line .= ' ' . Util::json($this->details);
        }

        return $line;
    }
}
