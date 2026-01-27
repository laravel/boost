<?php

declare(strict_types=1);

namespace Laravel\Boost\Exceptions;

use Exception;
use JsonException;

class BoostException extends Exception
{
    public static function requestFailed(string $body): self
    {
        try {
            $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
            $body = $decoded['message'] ?? $body;
        } catch (JsonException) {
            // Use raw body if not valid JSON
        }

        return new self($body);
    }
}
