<?php

    namespace Socialbox\Objects\Standard;

    use DateTime;
    use Socialbox\Enums\Flags\SessionFlags;
    use Socialbox\Interfaces\SerializableInterface;

    class SessionState implements SerializableInterface
    {
        private string $uuid;
        private string $identifiedAs;
        private bool $authenticated;
        /**
         * @var SessionFlags[]|null
         */
        private ?array $flags;
        private DateTime $created;

        /**
         * Constructor for initializing the object with the provided data.
         *
         * @param array $data An associative array containing the values for initializing the object.
         *                    - 'uuid': string, Unique identifier.
         *                    - 'identified_as': mixed, The identity information.
         *                    - 'authenticated': bool, Whether the object is authenticated.
         *                    - 'flags': string|null, Optional flags in
         * @throws \DateMalformedStringException
         */
        public function __construct(array $data)
        {
            $this->uuid = $data['uuid'];
            $this->identifiedAs = $data['identified_as'];
            $this->authenticated = $data['authenticated'];

            if(is_string($data['flags']))
            {
                $this->flags = SessionFlags::fromString($data['flags']);
            }
            elseif(is_array($data['flags']))
            {
                $this->flags = $data['flags'];
            }
            else
            {
                $this->flags = null;
            }

            if(is_int($data['created']))
            {
                $this->created = new DateTime();
                $this->created->setTimestamp($data['created']);
            }
            elseif($data['created'] instanceof DateTime)
            {
                $this->created = $data['created'];
            }
            else
            {
                $this->created = new DateTime($data['created']);
            }
        }

        public function getUuid(): string
        {
            return $this->uuid;
        }

        public function getIdentifiedAs(): string
        {
            return $this->identifiedAs;
        }

        public function isAuthenticated(): bool
        {
            return $this->authenticated;
        }

        public function getFlags(): ?array
        {
            return $this->flags;
        }

        public function getCreated(): DateTime
        {
            return $this->created;
        }

        public static function fromArray(array $data): SessionState
        {
            return new self($data);
        }

        public function toArray(): array
        {
            return [
                'uuid' => $this->uuid,
                'identified_as' => $this->identifiedAs,
                'authenticated' => $this->authenticated,
                'flags' => $this->flags,
                'created' => $this->created->getTimestamp()
            ];
        }
    }