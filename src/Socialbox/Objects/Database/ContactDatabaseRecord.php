<?php

    namespace Socialbox\Objects\Database;

    use DateMalformedStringException;
    use DateTime;
    use InvalidArgumentException;
    use Socialbox\Enums\Types\ContactRelationshipType;
    use Socialbox\Interfaces\SerializableInterface;
    use Socialbox\Objects\Standard\Contact;

    class ContactDatabaseRecord implements SerializableInterface
    {
        private string $uuid;
        private string $peerUuid;
        private string $contactPeerAddress;
        private ContactRelationshipType $relationship;
        private DateTime $created;

        /**
         * Constructor for initializing the class with provided data.
         *
         * @param array $data An associative array containing the initialization data:
         *                    - 'uuid': string The unique identifier.
         *                    - 'peer_uuid': string The peer unique identifier.
         *                    - 'contact_peer_address': string The contact peer address.
         *                    - 'relationship': mixed The contact relationship type, as a string or ContactRelationshipType.
         *                    - 'created': mixed The creation date as a string, integer timestamp, or DateTime instance.
         * @throws DateMalformedStringException If the created date is not a valid date string.
         * @throws InvalidArgumentException If one or more of the provided data is invalid.
         */
        public function __construct(array $data)
        {
            $this->uuid = $data['uuid'];
            $this->peerUuid = $data['peer_uuid'];
            $this->contactPeerAddress = $data['contact_peer_address'];

            if(is_string($data['relationship']))
            {
                $this->relationship = ContactRelationshipType::from($data['relationship']);
            }
            elseif($data['relationship'] instanceof ContactRelationshipType)
            {
                $this->relationship = $data['relationship'];
            }
            else
            {
                throw new InvalidArgumentException('Invalid relationship type');
            }

            if(is_int($data['created']))
            {
                $this->created = (new DateTime())->setTimestamp($data['created']);
            }
            elseif(is_string($data['created']))
            {
                $this->created = new DateTime($data['created']);
            }
            elseif($data['created'] instanceof DateTime)
            {
                $this->created = $data['created'];
            }
            else
            {
                throw new InvalidArgumentException('Invalid created date');
            }
        }

        /**
         *
         * @return string Returns the UUID as a string.
         */
        public function getUuid(): string
        {
            return $this->uuid;
        }

        /**
         * Retrieves the UUID of the peer.
         *
         * @return string The UUID of the peer.
         */
        public function getPeerUuid(): string
        {
            return $this->peerUuid;
        }

        /**
         * Retrieves the contact peer address.
         *
         * @return string The contact peer address.
         */
        public function getContactPeerAddress(): string
        {
            return $this->contactPeerAddress;
        }

        /**
         * Retrieves the relationship type of the contact.
         *
         * @return ContactRelationshipType The relationship type of the contact.
         */
        public function getRelationship(): ContactRelationshipType
        {
            return $this->relationship;
        }

        /**
         * Retrieves the created date and time.
         *
         * @return DateTime The DateTime object representing when the entity was created.
         */
        public function getCreated(): DateTime
        {
            return $this->created;
        }

        /**
         * @inheritDoc
         */
        public static function fromArray(array $data): ContactDatabaseRecord
        {
            return new self($data);
        }

        /**
         * Converts the object to a standard contact record.
         *
         * @return Contact The standard contact record.
         */
        public function toStandard(): Contact
        {
            return new Contact([
                'address' => $this->contactPeerAddress,
                'relationship' => $this->relationship,
                'added_timestamp' => $this->created->getTimestamp()
            ]);
        }

        /**
         * @inheritDoc
         */
        public function toArray(): array
        {
            return [
                'uuid' => $this->uuid,
                'peer_uuid' => $this->peerUuid,
                'contact_peer_address' => $this->contactPeerAddress,
                'relationship' => $this->relationship->value,
                'created' => $this->created->format('Y-m-d H:i:s')
            ];
        }
    }