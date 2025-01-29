<?php

    namespace Socialbox\Objects\Standard;

    use Socialbox\Interfaces\SerializableInterface;

    class TextCaptchaVerification implements SerializableInterface
    {
        private int $expires;
        private string $question;

        /**
         * ImageCaptcha constructor.
         * @param array $data
         */
        public function __construct(array $data)
        {
            $this->expires = $data['expires'];
            $this->question = $data['question'];
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
         * Returns the question of the captcha
         *
         * @return string The question of the captcha
         */
        public function getQuestion(): string
        {
            return $this->question;
        }

        /**
         * @inheritDoc
         */
        public static function fromArray(array $data): TextCaptchaVerification
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
                'question' => $this->question
            ];
        }
    }