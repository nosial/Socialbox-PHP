<?php

    namespace Socialbox\Objects\Standard;

    use Socialbox\Interfaces\SerializableInterface;

    class ExternalUrlVerification implements SerializableInterface
    {
        private int $expires;
        private string $url;

        /**
         * ImageCaptcha constructor.
         * @param array $data
         */
        public function __construct(array $data)
        {
            $this->expires = $data['expires'];
            $this->url = $data['url'];
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
         * Returns the URL of the external verification
         *
         * @return string The URL of the external verification
         */
        public function getUrl(): string
        {
            return $this->url;
        }

        /**
         * @inheritDoc
         */
        public static function fromArray(array $data): ExternalUrlVerification
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
                'url' => $this->url
            ];
        }
    }