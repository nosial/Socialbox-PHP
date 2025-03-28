<?php

    namespace Socialbox\Objects\Standard;

    use InvalidArgumentException;
    use Socialbox\Enums\Flags\PeerFlags;
    use Socialbox\Enums\Types\InformationFieldName;
    use Socialbox\Interfaces\SerializableInterface;
    use Socialbox\Objects\Database\PeerInformationFieldRecord;
    use Socialbox\Objects\PeerAddress;

    class Peer implements SerializableInterface
    {
        private PeerAddress $address;
        /**
         * @var InformationField[]
         */
        private array $informationFields;
        private array $flags;
        private int $registered;

        /**
         * Constructor method.
         *
         * @param array $data An associative array containing the keys:
         *                    - 'address': A string or an instance of PeerAddress. Used to set the address property.
         *                    - 'display_name': The display name for the entity.
         *                    - 'flags': Flags associated with the entity.
         *                    - 'registered': Registration status or date for the entity.
         *
         * @return void
         * @throws InvalidArgumentException If the 'address' value is neither a string nor an instance of PeerAddress.
         */
        public function __construct(array $data)
        {
            if(is_string($data['address']))
            {
                $this->address = PeerAddress::fromAddress($data['address']);
            }
            elseif($data['address'] instanceof PeerAddress)
            {
                $this->address = $data['address'];
            }
            else
            {
                throw new InvalidArgumentException('Invalid address value type, got type ' . gettype($data['address']));
            }

            $informationFields = [];
            foreach($data['information_fields'] as $field)
            {
                if($field instanceof PeerInformationFieldRecord)
                {
                    $informationFields[] = $field->toInformationField();
                }
                elseif($field instanceof InformationFieldState)
                {
                    $informationFields[] = $field->toInformationField();
                }
                elseif(is_array($field))
                {
                    $informationFields[] = new InformationField($field);
                }
                else
                {
                    throw new InvalidArgumentException('Invalid information field type, got type ' . gettype($field));
                }
            }

            $this->informationFields = $informationFields;

            if(is_array($data['flags']))
            {
                $this->flags = [];
                foreach($data['flags'] as $flag)
                {
                    if($flag instanceof PeerFlags)
                    {
                        $this->flags[] = $flag->value;
                    }
                    elseif(is_string($flag))
                    {
                        $this->flags[] = $flag;
                    }
                    else
                    {
                        throw new InvalidArgumentException('Invalid flag type, got type ' . gettype($flag));
                    }
                }
            }
            elseif(is_string($data['flags']))
            {
                $this->flags = PeerFlags::fromString($data['flags']);
            }
            else
            {
                throw new InvalidArgumentException('Invalid flags value type, got type ' . gettype($data['flags']));
            }

            $this->registered = $data['registered'];
        }

        /**
         * Retrieves the address property.
         *
         * @return PeerAddress The address associated with the instance.
         */
        public function getPeerAddress(): PeerAddress
        {
            return $this->address;
        }

        /**
         * Retrieves the information fields associated with the peer.
         *
         * @return InformationField[] An array containing the information fields.
         */
        public function getInformationFields(): array
        {
            return $this->informationFields;
        }

        /**
         * Retrieves the information field associated with the peer.
         *
         * @param InformationFieldName $name The name of the information field to retrieve.
         * @return InformationField|null The information field associated with the peer.
         */
        public function getInformationField(InformationFieldName $name): ?InformationField
        {
            foreach($this->informationFields as $field)
            {
                if($field->getName() == $name)
                {
                    return $field;
                }
            }
            return null;
        }

        /**
         * Checks if the information field exists.
         *
         * @param InformationFieldName $name The name of the information field to check.
         * @return bool True if the information field exists, false otherwise.
         */
        public function informationFieldExists(InformationFieldName $name): bool
        {
            return $this->getInformationField($name) !== null;
        }

        /**
         * Retrieves the flags associated with the entity.
         *
         * @return array An array containing the flags.
         */
        public function getFlags(): array
        {
            return $this->flags;
        }

        /**
         * Retrieves the registered value.
         *
         * @return int The registered property value.
         */
        public function getRegistered(): int
        {
            return $this->registered;
        }

        /**
         * @inheritDoc
         */
        public static function fromArray(array $data): Peer
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
                'information_fields' => array_map(fn($field) => $field->toArray(), $this->informationFields),
                'flags' => $this->flags,
                'registered' => $this->registered
            ];
        }
    }