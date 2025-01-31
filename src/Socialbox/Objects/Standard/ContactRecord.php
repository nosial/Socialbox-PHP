<?php

    namespace Socialbox\Objects\Standard;

    use InvalidArgumentException;
    use Socialbox\Enums\Types\ContactRelationshipType;
    use Socialbox\Interfaces\SerializableInterface;
    use Socialbox\Objects\PeerAddress;

    class ContactRecord implements SerializableInterface
    {
        private PeerAddress $address;
        private ?Peer $peer;
        private ContactRelationshipType $relationship;
        /**
         * @var KnownSigningKey[]
         */
        private array $knownKeys;
        private int $addedTimestamp;

        /**
         * Constructs a new instance with the provided parameters.
         *
         * @param array $data The array of data to use for the object.
         */
        public function __construct(array $data)
        {
            $this->address = PeerAddress::fromAddress($data['address']);
            if(is_array($data['peer']))
            {
                $this->peer = Peer::fromArray($data['peer']);
            }
            elseif($data['peer'] instanceof Peer)
            {
                $this->peer = $data['peer'];
            }
            elseif(is_null($data['peer']))
            {
                $this->peer = null;
            }
            else
            {
                throw new InvalidArgumentException('Invalid peer data');
            }

            if($data['relationship'] instanceof ContactRelationshipType)
            {
                $this->relationship = $data['relationship'];
            }
            elseif(is_string($data['relationship']))
            {
                $this->relationship = ContactRelationshipType::tryFrom($data['relationship']) ?? ContactRelationshipType::MUTUAL;
            }
            else
            {
                throw new InvalidArgumentException('Invalid relationship data');
            }

            $this->knownKeys = [];

            foreach($data['known_keys'] as $key)
            {
                if(is_array($key))
                {
                    $this->knownKeys[] = KnownSigningKey::fromArray($key);
                }
                elseif($key instanceof KnownSigningKey)
                {
                    $this->knownKeys[] = $key;
                }
                else
                {
                    throw new InvalidArgumentException('Invalid known key data');
                }
            }
            $this->addedTimestamp = $data['added_timestamp'];
        }

        /**
         * Retrieves the address of the contact.
         *
         * @return PeerAddress Returns the address of the contact.
         */
        public function getAddress(): PeerAddress
        {
            return $this->address;
        }

        /**
         * Retrieves the peer of the contact.
         *
         * @return Peer|null Returns the peer of the contact. If the peer is not known, null is returned.
         */
        public function getPeer(): ?Peer
        {
            return $this->peer;
        }

        /**
         * Retrieves the relationship of the contact.
         *
         * @return ContactRelationshipType Returns the relationship of the contact.
         */
        public function getRelationship(): ContactRelationshipType
        {
            return $this->relationship;
        }

        /**
         * Retrieves the known keys of the contact.
         *
         * @return KnownSigningKey[] Returns the known keys of the contact.
         */
        public function getKnownKeys(): array
        {
            return $this->knownKeys;
        }

        /**
         * Retrieves the timestamp when the contact was added.
         *
         * @return int Returns the timestamp when the contact was added.
         */
        public function getAddedTimestamp(): int
        {
            return $this->addedTimestamp;
        }

        /**
         * @inheritDoc
         */
        public static function fromArray(array $data): ContactRecord
        {
            return new self($data);
        }

        /**
         * @inheritDoc
         */
        public function toArray(): array
        {
            return [
                'address' => $this->address->getAddress(),
                'peer' => $this->peer?->toArray(),
                'relationship' => $this->relationship->value,
                'known_keys' => array_map(function($key) {return $key->toArray();}, $this->knownKeys),
                'added_timestamp' => $this->addedTimestamp
            ];
        }
    }