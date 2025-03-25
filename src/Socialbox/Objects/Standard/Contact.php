<?php

    namespace Socialbox\Objects\Standard;

    use InvalidArgumentException;
    use Socialbox\Enums\Types\ContactRelationshipType;
    use Socialbox\Interfaces\SerializableInterface;
    use Socialbox\Objects\Database\ContactKnownKeyRecord;
    use Socialbox\Objects\PeerAddress;

    class Contact implements SerializableInterface
    {
        private PeerAddress $address;
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
                elseif($key instanceof ContactKnownKeyRecord)
                {
                    $this->knownKeys[] = $key->toStandard();
                }
                else
                {
                    throw new InvalidArgumentException('Invalid known key data, got ' . gettype($key));
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
         * Checks if the contact has a signature with the given UUID.
         *
         * @param string $signatureUuid The UUID of the signature to check for.
         *
         * @return bool Returns true if the signature exists, otherwise false.
         */
        public function signatureExists(string $signatureUuid): bool
        {
            foreach($this->knownKeys as $key)
            {
                if($key->getUuid() === $signatureUuid)
                {
                    return true;
                }
            }
            return false;
        }

        /**
         * Retrieves the signature by the UUID.
         *
         * @param string $signatureUuid The UUID of the signature to retrieve.
         * @return KnownSigningKey|null Returns the signature if found, otherwise null.
         */
        public function getSignature(string $signatureUuid): ?KnownSigningKey
        {
            foreach($this->knownKeys as $key)
            {
                if($key->getUuid() === $signatureUuid)
                {
                    return $key;
                }
            }
            return null;
        }

        /**
         * Checks if the contact has a signature with the given public key.
         *
         * @param string $publicSignatureKey The public key of the signature to check for.
         * @return bool Returns true if the signature exists, otherwise false.
         */
        public function signatureKeyExists(string $publicSignatureKey): bool
        {
            foreach($this->knownKeys as $key)
            {
                if($key->getPublicKey() === $publicSignatureKey)
                {
                    return true;
                }
            }
            return false;
        }

        /**
         * Retrieves the signature key by the public key.
         *
         * @param string $publicSignatureKey The public key of the signature key to retrieve.
         * @return KnownSigningKey|null Returns the signature key if found, otherwise null.
         */
        public function getSignatureByPublicKey(string $publicSignatureKey): ?KnownSigningKey
        {
            foreach($this->knownKeys as $key)
            {
                if($key->getPublicKey() === $publicSignatureKey)
                {
                    return $key;
                }
            }
            return null;
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
        public static function fromArray(array $data): Contact
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
                'relationship' => $this->relationship->value,
                'known_keys' => array_map(function($key) {return $key->toArray();}, $this->knownKeys),
                'added_timestamp' => $this->addedTimestamp
            ];
        }
    }