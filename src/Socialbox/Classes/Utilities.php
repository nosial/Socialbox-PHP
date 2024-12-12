<?php

namespace Socialbox\Classes;

use DateTime;
use InvalidArgumentException;
use JsonException;
use RuntimeException;
use Socialbox\Enums\StandardHeaders;
use Socialbox\Objects\PeerAddress;
use Throwable;

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
        catch(JsonException $e)
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

    /**
     * Converts a Throwable object into a formatted string.
     *
     * @param Throwable $e The throwable to be converted into a string.
     * @return string The formatted string representation of the throwable, including the exception class, message, file, line, and stack trace.
     */
    public static function throwableToString(Throwable $e): string
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

    /**
     * Generates a formatted header string.
     *
     * @param StandardHeaders $header The standard header object.
     * @param string $value The header value to be associated with the standard header.
     * @return string The formatted header string.
     */
    public static function generateHeader(StandardHeaders $header, string $value): string
    {
        return $header->value . ': ' . $value;
    }

    /**
     * Generates a random string of specified length using the provided character set.
     *
     * @param int $int The length of the random string to be generated.
     * @param string $string The character set to use for generating the random string.
     * @return string The generated random string.
     */
    public static function randomString(int $int, string $string): string
    {
        $characters = str_split($string);
        $randomString = '';

        for ($i = 0; $i < $int; $i++)
        {
            $randomString .= $characters[array_rand($characters)];
        }

        return $randomString;
    }

    /**
     * Generates a random CRC32 hash.
     *
     * @return string The generated CRC32 hash as a string.
     */
    public static function randomCrc32(): string
    {
        return hash('crc32b', uniqid());
    }

    /**
     * Sanitizes a file name by removing any characters that are not alphanumeric, hyphen, or underscore.
     *
     * @param string $name The file name to be sanitized.
     * @return string The sanitized file name.
     */
    public static function sanitizeFileName(string $name): string
    {
        return preg_replace('/[^a-zA-Z0-9-_]/', '', $name);
    }

    /**
     * Converts an array into a serialized string by joining the elements with a comma.
     *
     * @param array $list An array of elements that need to be converted to a comma-separated string.
     * @return string A string representation of the array elements, joined by commas.
     */
    public static function serializeList(array $list): string
    {
        return implode(',', $list);
    }

    /**
     * Converts a serialized string into an array by splitting the string at each comma.
     *
     * @param string $list A comma-separated string that needs to be converted to an array.
     * @return array An array of string values obtained by splitting the input string.
     */
    public static function unserializeList(string $list): array
    {
        return explode(',', $list);
    }
}