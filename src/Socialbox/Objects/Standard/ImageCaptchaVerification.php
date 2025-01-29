<?php

    namespace Socialbox\Objects\Standard;

    use Exception;
    use Socialbox\Interfaces\SerializableInterface;

    class ImageCaptchaVerification implements SerializableInterface
    {
        private int $expires;
        private string $imageBase64;

        /**
         * ImageCaptcha constructor.
         * @param array $data
         */
        public function __construct(array $data)
        {
            $this->expires = $data['expires'];
            $this->imageBase64 = $data['image_base64'];
        }

        /**
         * Returns the expiration time of the captcha
         *
         * @return int The expiration time of the captcha in Unix timestamp format
         */
        public function getExpires(): int
        {
            return $this->expires;
        }

        /**
         * Returns the image data of the captcha
         *
         * @return string The image data of the captcha
         */
        public function getImageBase64(): string
        {
            return $this->imageBase64;
        }

        /**
         * Saves the image to the specified path
         *
         * @param string $path The path to save the image to
         * @throws Exception If the image could not be saved
         */
        public function saveImage(string $path): void
        {
            // Check if the base64 is prefixed with html data
            if (str_starts_with($this->imageBase64, 'data:image/jpeg;base64,'))
            {
                $this->imageBase64 = substr($this->imageBase64, strlen('data:image/jpeg;base64,'));
            }

            $decoded = @base64_decode($this->imageBase64);
            if($decoded === false)
            {
                throw new Exception('Failed to decode base64 image');
            }

            if(file_put_contents($path, $decoded) === false)
            {
                throw new Exception('Failed to save image');
            }
        }

        /**
         * @inheritDoc
         */
        public static function fromArray(array $data): ImageCaptchaVerification
        {
            return new self($data);
        }

        /**
         * @inheritDoc
         */
        public function toArray(): array
        {
            return [
                'expires' => $this->expires,
                'image_base64' => $this->imageBase64
            ];
        }
    }