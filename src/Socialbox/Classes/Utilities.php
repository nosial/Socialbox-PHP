<?php

    namespace Socialbox\Classes;

    use Exception;
    use InvalidArgumentException;
    use JsonException;
    use RuntimeException;
    use Socialbox\Enums\StandardHeaders;
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

        public static function throwableToString(Throwable $e, int $level=0): string
        {
            // Indentation for nested exceptions
            $indentation = str_repeat('  ', $level);

            // Basic information about the Throwable
            $type = get_class($e);
            $message = $e->getMessage() ?: 'No message';
            $file = $e->getFile() ?: 'Unknown file';
            $line = $e->getLine() ?? 'Unknown line';

            // Compose the base string representation of this Throwable
            $result = sprintf("%s%s: %s\n%s  in %s on line %s\n",
                $indentation, $type, $message, $indentation, $file, $line
            );

            // Append stack trace if available
            $stackTrace = $e->getTraceAsString();
            if (!empty($stackTrace))
            {
                $result .= $indentation . "  Stack trace:\n" . $indentation . "  " . str_replace("\n", "\n" . $indentation . "  ", $stackTrace) . "\n";
            }

            // Recursively append the cause if it exists
            $previous = $e->getPrevious();
            if ($previous)
            {
                $result .= $indentation . "Caused by:\n" . self::throwableToString($previous, $level + 1);
            }

            return $result;
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
         * Sanitizes a Base64-encoded JPEG image by validating its data, decoding it,
         * and re-encoding it to ensure it conforms to the JPEG format.
         *
         * @param string $data The Base64-encoded string potentially containing a JPEG image,
         *                     optionally prefixed with "data:image/...;base64,".
         * @return string A sanitized and re-encoded JPEG image as a binary string.
         * @throws InvalidArgumentException If the input data is not valid Base64,
         *                                  does not represent an image, or is not in the JPEG format.
         */
        public static function sanitizeJpeg(string $data): string
        {
            // Temporarily load the decoded data as an image
            $tempResource = imagecreatefromstring($data);

            // Validate that the decoded data is indeed an image
            if ($tempResource === false)
            {
                throw new InvalidArgumentException("The data does not represent a valid image.");
            }

            // Validate MIME type using getimagesizefromstring
            $imageInfo = getimagesizefromstring($data);
            if ($imageInfo === false || $imageInfo['mime'] !== 'image/jpeg')
            {
                imagedestroy($tempResource); // Cleanup resources
                throw new InvalidArgumentException("The image is not a valid JPEG format.");
            }

            // Capture the re-encoded image in memory and return it as a string
            ob_start(); // Start output buffering
            $saveResult = imagejpeg($tempResource, null, 100); // Max quality, save to output buffer
            imagedestroy($tempResource); // Free up memory resources

            if (!$saveResult)
            {
                ob_end_clean(); // Clean the output buffer if encoding failed
                throw new InvalidArgumentException("Failed to encode the sanitized image.");
            }

            // Return the sanitized jpeg image as the result
            return ob_get_clean();
        }

        /**
         * Resizes an image to a specified width and height while maintaining its aspect ratio.
         * The resized image is centered on a black background matching the target dimensions.
         *
         * @param string $data The binary data of the source image.
         * @param int $width The desired width of the resized image.
         * @param int $height The desired height of the resized image.
         * @return string The binary data of the resized image in PNG format.
         * @throws InvalidArgumentException If the source image cannot be created from the provided data.
         * @throws Exception If image processing fails during resizing.
         */
        public static function resizeImage(string $data, int $width, int $height): string
        {
            try
            {
                // Create image resource from binary data
                $sourceImage = imagecreatefromstring($data);
                if (!$sourceImage)
                {
                    throw new InvalidArgumentException("Failed to create image from provided data");
                }

                // Get original dimensions
                $sourceWidth = imagesx($sourceImage);
                $sourceHeight = imagesy($sourceImage);

                // Calculate aspect ratios
                $sourceRatio = $sourceWidth / $sourceHeight;
                $targetRatio = $width / $height;

                // Initialize dimensions for scaling
                $scaleWidth = $width;
                $scaleHeight = $height;

                // Calculate scaling dimensions to maintain aspect ratio
                if ($sourceRatio > $targetRatio)
                {
                    // Source image is wider - scale by width
                    $scaleHeight = $width / $sourceRatio;
                }
                else
                {
                    // Source image is taller - scale by height
                    $scaleWidth = $height * $sourceRatio;
                }

                // Create target image with desired dimensions
                $targetImage = imagecreatetruecolor($width, $height);
                if (!$targetImage)
                {
                    throw new Exception("Failed to create target image");
                }

                // Fill background with black
                $black = imagecolorallocate($targetImage, 0, 0, 0);
                imagefill($targetImage, 0, 0, $black);

                // Calculate padding to center the scaled image
                $paddingX = ($width - $scaleWidth) / 2;
                $paddingY = ($height - $scaleHeight) / 2;

                // Enable alpha blending
                imagealphablending($targetImage, true);
                imagesavealpha($targetImage, true);

                // Resize and copy the image with high-quality resampling
                if (!imagecopyresampled($targetImage, $sourceImage, (int)$paddingX, (int)$paddingY, 0, 0, (int)$scaleWidth, (int)$scaleHeight, $sourceWidth, $sourceHeight))
                {
                    throw new Exception("Failed to resize image");
                }

                // Start output buffering
                ob_start();

                // Output image as PNG (you can modify this to support other formats)
                imagepng($targetImage);

                // Return the image data
                return ob_get_clean();
            }
            finally
            {
                if (isset($sourceImage))
                {
                    imagedestroy($sourceImage);
                }

                if (isset($targetImage))
                {
                    imagedestroy($targetImage);
                }
            }
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

        /**
         * Checks if the given HTTP response code indicates success or failure.
         *
         * @param int $responseCode The HTTP response code to check.
         * @return bool True if the response code indicates success, false otherwise.
         */
        public static function isSuccessCodes(int $responseCode): bool
        {
            return $responseCode >= 200 && $responseCode < 300;
        }
    }