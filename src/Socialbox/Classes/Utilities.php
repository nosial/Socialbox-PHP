<?php

namespace Socialbox\Classes;

use InvalidArgumentException;
use RuntimeException;
use Socialbox\Enums\StandardHeaders;

class Utilities
{
    /**
     * Decodes a JSON string into an associative array, throws an exception if the JSON is invalid
     *
     * @param string $json The JSON string to decode
     * @return array The decoded associative array
     * @throws InvalidArgumentException If the JSON is invalid
     */
    public static function jsonDecode(string $json): array
    {
        $decoded = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE)
        {
            throw match (json_last_error())
            {
                JSON_ERROR_DEPTH => new InvalidArgumentException("JSON decoding failed: Maximum stack depth exceeded"),
                JSON_ERROR_STATE_MISMATCH => new InvalidArgumentException("JSON decoding failed: Underflow or the modes mismatch"),
                JSON_ERROR_CTRL_CHAR => new InvalidArgumentException("JSON decoding failed: Unexpected control character found"),
                JSON_ERROR_SYNTAX => new InvalidArgumentException("JSON decoding failed: Syntax error, malformed JSON"),
                JSON_ERROR_UTF8 => new InvalidArgumentException("JSON decoding failed: Malformed UTF-8 characters, possibly incorrectly encoded"),
                default => new InvalidArgumentException("JSON decoding failed: Unknown error"),
            };
        }

        return $decoded;
    }

    public static function jsonEncode(mixed $data): string
    {
        try
        {
            return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        }
        catch(\JsonException $e)
        {
            throw new InvalidArgumentException("Failed to encode json input", $e);
        }
    }

    /**
     * Encodes the given data in Base64.
     *
     * @param string $data The data to be encoded.
     * @return string The Base64 encoded string.
     * @throws InvalidArgumentException if the encoding fails.
     */
    public static function base64encode(string $data): string
    {
        $encoded = base64_encode($data);

        if (!$encoded)
        {
            throw new InvalidArgumentException('Failed to encode data in Base64');
        }

        return $encoded;
    }

    /**
     * Decodes a Base64 encoded string.
     *
     * @param string $data The Base64 encoded data to be decoded.
     * @return string The decoded data.
     * @throws InvalidArgumentException If decoding fails.
     */
    public static function base64decode(string $data): string
    {
        $decoded = base64_decode($data, true);

        if ($decoded === false)
        {
            throw new InvalidArgumentException('Failed to decode data from Base64');
        }

        return $decoded;
    }

    /**
     * Returns the request headers as an associative array
     *
     * @return array
     */
    public static function getRequestHeaders(): array
    {
        // Check if function getallheaders() exists
        if (function_exists('getallheaders'))
        {
            $headers = getallheaders();
        }
        else
        {
            // Fallback for servers where getallheaders() is not available
            $headers = [];
            foreach ($_SERVER as $key => $value)
            {
                if (str_starts_with($key, 'HTTP_'))
                {
                    // Convert header names to the normal HTTP format
                    $headers[str_replace('_', '-', strtolower(substr($key, 5)))] = $value;
                }
            }
        }

        if($headers === false)
        {
            throw new RuntimeException('Failed to get request headers');
        }

        return $headers;
    }

    public static function throwableToString(\Throwable $e): string
    {
        return sprintf(
            "%s: %s in %s:%d\nStack trace:\n%s",
            get_class($e),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            $e->getTraceAsString()
        );
    }

    public static function generateHeader(StandardHeaders $header, string $value): string
    {
        return $header->value . ': ' . $value;
    }
}