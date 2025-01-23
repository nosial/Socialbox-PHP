<?php

    namespace Socialbox\Objects\Standard;

    use Socialbox\Enums\Types\ContactRelationshipType;
    use Socialbox\Interfaces\SerializableInterface;
    use Socialbox\Objects\PeerAddress;

    class ContactRecord implements SerializableInterface
    {
        private PeerAddress $address;
        private ContactRelationshipType $relationship;
        private int $addedTimestamp;

        /**
         * Constructs a new instance with the provided parameters.
         *
         * @param array $data The array of data to use for the object.
         */
        public function __construct(array $data)
        {
            $this->address = PeerAddress::fromAddress($data['address']);
            $this->relationship = ContactRelationshipType::tryFrom($data['relationship']) ?? ContactRelationshipType::MUTUAL;
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
                'address' => $this->address->getAddress(),
                'relationship' => $this->relationship->value,
                'added_timestamp' => $this->addedTimestamp
            ];
        }
    }