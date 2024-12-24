<?php

    namespace Socialbox\Objects\Standard;

    use Socialbox\Interfaces\SerializableInterface;

    class ServerDocument implements SerializableInterface
    {
        private int $lastUpdated;
        private string $title;
        private string $content;

        /**
         * Constructor method to initialize the object with provided data.
         *
         * @param array $data An associative array containing 'last_updated', 'content_type', 'title', and 'content' keys.
         * @return void
         */
        public function __construct(array $data)
        {
            $this->lastUpdated = $data['last_updated'];
            $this->title = $data['title'];
            $this->content = $data['content'];
        }

        /**
         * Retrieves the timestamp of the last update.
         *
         * @return int The last updated timestamp.
         */
        public function getLastUpdated(): int
        {
            return $this->lastUpdated;
        }

        /**
         * Retrieves the title property.
         *
         * @return string The title value.
         */
        public function getTitle(): string
        {
            return $this->title;
        }

        /**
         * Retrieves the content stored in the instance.
         *
         * @return string The content as a string.
         */
        public function getContent(): string
        {
            return $this->content;
        }

        /**
         * @inheritDoc
         */
        public static function fromArray(array $data): object
        {
            return new self($data);
        }

        /**
         * @inheritDoc
         */
        public function toArray(): array
        {
            return [
                'last_updated' => $this->lastUpdated,
                'content_type' => $this->contentType,
                'title' => $this->title,
                'content' => $this->content
            ];
        }
    }